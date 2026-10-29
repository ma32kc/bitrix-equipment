<?php

/**
 * Содержимое вкладки "Оборудование" для карточки смарт-процесса
 * Вызывается ajax-загрузчиком вкладки (см. CrmTabs::onTabsInitialized),
 * возвращает HTML с компонентом списка, отфильтрованным по сделке
 */

use Bitrix\Main\Loader;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

global $APPLICATION, $USER;

if (!$USER->IsAuthorized())
{
	require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
	die();
}

$dealId = isset($_REQUEST['dealId']) ? (int)$_REQUEST['dealId'] : 0;

if (Loader::includeModule('custom.equipment'))
{
	$APPLICATION->IncludeComponent(
		'custom:equipment.list',
		'',
		[
			'DEAL_ID'   => $dealId,
			'GRID_ID'   => 'custom_equipment_grid_' . $dealId,
			'FILTER_ID' => 'custom_equipment_filter_' . $dealId,
		]
	);
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php';
