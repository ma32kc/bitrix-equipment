<?php

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;

if (Loader::includeModule('custom.equipment'))
{
	// Вкладка "Оборудование" в карточке смарт-процесса
	// и подключение счётчика в карточке сделки - один обработчик на оба случая
	EventManager::getInstance()->addEventHandler(
		'crm',
		'onEntityDetailsTabsInitialized',
		['Custom\\Equipment\\Integration\\CrmTabs', 'onTabsInitialized']
	);
}
