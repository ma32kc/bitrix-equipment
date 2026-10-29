<?php

namespace Custom\Equipment\Integration;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\UI\Extension;
use Custom\Equipment\Helper;

/**
 * Интеграция с карточками CRM (единый entity editor)
 *
 * Одно событие crm:onEntityDetailsTabsInitialized обслуживает обе задачи:
 *  - в карточке смарт-процесса добавляет вкладку "Оборудование"
 *  - в карточке сделки подключает JS-расширение со счётчиком
 */
class CrmTabs
{
	/**
	 * Обработчик события crm:onEntityDetailsTabsInitialized.
	 *
	 * @param Event $event
	 * @return EventResult|null
	 */
	public static function onTabsInitialized(Event $event)
	{
		$entityTypeId = (int)$event->getParameter('entityTypeId');
		$entityId     = (int)$event->getParameter('entityID');

		// Карточка сделки - подключаем расширение со счётчиком оборудования
		if ($entityTypeId === \CCrmOwnerType::Deal)
		{
			Extension::load('custom.deal_equipment_count');
			return null;
		}

		// Карточка нашего смарт-процесса - добавляем вкладку с оборудованием
		if ($entityTypeId === Helper::getSmartProcessEntityTypeId() && $entityTypeId > 0)
		{
			$dealId = self::resolveParentDealId($entityTypeId, $entityId);

			$tabs = (array)$event->getParameter('tabs');
			$tabs[] = [
				'id'   => 'custom_equipment',
				'name' => 'Оборудование',
				'loader' => [
					'serviceUrl' => '/local/ajax/equipment_tab.php?dealId=' . $dealId . '&siteId=' . SITE_ID,
				],
			];

			return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs], 'crm');
		}

		return null;
	}

	/** Сделка, к которой привязан элемент смарт-процесса (parent) */
	private static function resolveParentDealId(int $entityTypeId, int $entityId): int
	{
		if ($entityId <= 0 || !\Bitrix\Main\Loader::includeModule('crm'))
		{
			return 0;
		}

		$factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($entityTypeId);
		if (!$factory)
		{
			return 0;
		}

		$item = $factory->getItem($entityId);
		if (!$item)
		{
			return 0;
		}

		return (int)$item->getParentId(\CCrmOwnerType::Deal);
	}
}
