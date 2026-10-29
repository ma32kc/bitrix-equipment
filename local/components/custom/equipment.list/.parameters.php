<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arComponentParameters = [
	'PARAMETERS' => [
		'DEAL_ID' => [
			'PARENT'  => 'BASE',
			'NAME'    => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_P_DEAL_ID'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
		],
		'GRID_ID' => [
			'PARENT'  => 'BASE',
			'NAME'    => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_P_GRID_ID'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'custom_equipment_grid',
		],
		'FILTER_ID' => [
			'PARENT'  => 'BASE',
			'NAME'    => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_P_FILTER_ID'),
			'TYPE'    => 'STRING',
			'DEFAULT' => 'custom_equipment_filter',
		],
		'PAGE_SIZE' => [
			'PARENT'  => 'BASE',
			'NAME'    => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_P_PAGE_SIZE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '20',
		],
	],
];
