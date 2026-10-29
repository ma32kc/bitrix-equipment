<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

/** @var array $arParams */
/** @var array $arResult */
/** @var CMain $APPLICATION */
/** @var CBitrixComponentTemplate $this */

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$filterId = $arParams['FILTER_ID'];
$gridId   = $arParams['GRID_ID'];

// Фильтр показываем только когда есть контекст сделки.
if (!empty($arResult['FILTER'])):
	$APPLICATION->IncludeComponent(
		'bitrix:main.ui.filter',
		'',
		[
			'FILTER_ID' => $filterId,
			'GRID_ID'   => $gridId,
			'FILTER'    => $arResult['FILTER'],
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL'       => true,
		],
		$this->getComponent(),
		['HIDE_ICONS' => true]
	);
endif;

$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	[
		'GRID_ID' => $gridId,
		'COLUMNS' => $arResult['COLUMNS'],
		'ROWS'    => $arResult['ROWS'],
		'NAV_OBJECT'           => $arResult['NAV_OBJECT'] ?? null,
		'SHOW_PAGINATION'      => true,
		'SHOW_TOTAL_COUNTER'   => true,
		'TOTAL_ROWS_COUNT'     => $arResult['TOTAL'] ?? 0,
		'SHOW_ROW_CHECKBOXES'  => false,
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_ACTIONS_MENU'     => false,
		'SHOW_GRID_SETTINGS_MENU'   => true,
		'SHOW_NAVIGATION_PANEL'     => true,
		'SHOW_SELECTED_COUNTER'     => false,
		'ALLOW_COLUMNS_SORT'        => true,
		'ALLOW_SORT'                => true,
		'EMPTY_TEXT'                => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_EMPTY'),
	],
	$this->getComponent()
);
