<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Type\DateTime;
use Bitrix\Highloadblock\HighloadBlockTable;

Loc::loadMessages(__FILE__);

class custom_equipment extends CModule
{
	public $MODULE_ID = 'custom.equipment';
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_NAME;
	public $MODULE_DESCRIPTION;
	public $PARTNER_NAME;

	/** Имя HL-блока и его физической таблицы */
	const HLBLOCK_NAME  = 'Equipment';
	const HLBLOCK_TABLE = 'custom_equipment';

	/** ID поля UF_TYPE, заполняется при создании полей */
	private $typeFieldId = 0;

	public function __construct()
	{
		$arModuleVersion = [];
		include __DIR__ . '/version.php';

		$this->MODULE_VERSION      = $arModuleVersion['VERSION'] ?? '1.0.0';
		$this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'] ?? '';
		$this->MODULE_NAME         = Loc::getMessage('CUSTOM_EQUIPMENT_MODULE_NAME');
		$this->MODULE_DESCRIPTION  = Loc::getMessage('CUSTOM_EQUIPMENT_MODULE_DESC');
		$this->PARTNER_NAME        = Loc::getMessage('CUSTOM_EQUIPMENT_PARTNER_NAME');
	}

	public function DoInstall()
	{
		ModuleManager::registerModule($this->MODULE_ID);

		Loader::includeModule('highloadblock');

		$hlblockId = $this->getHlblockId();
		if (!$hlblockId)
		{
			$hlblockId = $this->createHlblock();
			$this->createFields($hlblockId);
			$this->addDemoData($hlblockId);
		}

		return true;
	}

	public function DoUninstall()
	{
		Loader::includeModule('highloadblock');

		$hlblockId = $this->getHlblockId();
		if ($hlblockId)
		{
			// Поля и данные удаляются вместе с HL-блоком
			HighloadBlockTable::delete($hlblockId);
		}

		Option::delete($this->MODULE_ID);
		ModuleManager::unRegisterModule($this->MODULE_ID);

		return true;
	}

	/** Ищем HL-блок по имени, чтобы не плодить дубли при переустановке */
	private function getHlblockId()
	{
		$row = HighloadBlockTable::getRow([
			'select' => ['ID'],
			'filter' => ['=NAME' => self::HLBLOCK_NAME],
		]);

		return $row ? (int)$row['ID'] : 0;
	}

	private function createHlblock()
	{
		$result = HighloadBlockTable::add([
			'NAME'       => self::HLBLOCK_NAME,
			'TABLE_NAME' => self::HLBLOCK_TABLE,
		]);

		if (!$result->isSuccess())
		{
			throw new \RuntimeException(implode(', ', $result->getErrorMessages()));
		}

		return (int)$result->getId();
	}

	private function createFields($hlblockId)
	{
		$entityId = 'HLBLOCK_' . $hlblockId;
		$userType = new CUserTypeEntity();

		$userType->Add([
			'ENTITY_ID'    => $entityId,
			'FIELD_NAME'   => 'UF_NAME',
			'USER_TYPE_ID' => 'string',
			'MANDATORY'    => 'Y',
			'EDIT_FORM_LABEL' => ['ru' => 'Название', 'en' => 'Name'],
			'LIST_COLUMN_LABEL' => ['ru' => 'Название', 'en' => 'Name'],
		]);

		$this->typeFieldId = $userType->Add([
			'ENTITY_ID'    => $entityId,
			'FIELD_NAME'   => 'UF_TYPE',
			'USER_TYPE_ID' => 'enumeration',
			'MANDATORY'    => 'Y',
			'EDIT_FORM_LABEL' => ['ru' => 'Тип оборудования', 'en' => 'Equipment type'],
			'LIST_COLUMN_LABEL' => ['ru' => 'Тип оборудования', 'en' => 'Equipment type'],
		]);

		$this->addEnumValues($this->typeFieldId, ['Роутер', 'Ноутбук', 'Монитор']);

		$userType->Add([
			'ENTITY_ID'    => $entityId,
			'FIELD_NAME'   => 'UF_CREATED_TIME',
			'USER_TYPE_ID' => 'datetime',
			'MANDATORY'    => 'N',
			'EDIT_FORM_LABEL' => ['ru' => 'Дата создания', 'en' => 'Created'],
			'LIST_COLUMN_LABEL' => ['ru' => 'Дата создания', 'en' => 'Created'],
		]);

		$userType->Add([
			'ENTITY_ID'    => $entityId,
			'FIELD_NAME'   => 'UF_DEAL_ID',
			'USER_TYPE_ID' => 'integer',
			'MANDATORY'    => 'N',
			'EDIT_FORM_LABEL' => ['ru' => 'ID сделки', 'en' => 'Deal ID'],
			'LIST_COLUMN_LABEL' => ['ru' => 'ID сделки', 'en' => 'Deal ID'],
		]);
	}

	private function addEnumValues($fieldId, array $values)
	{
		$enum  = new CUserFieldEnum();
		$items = [];
		$i     = 0;

		foreach ($values as $value)
		{
			$items['n' . $i] = [
				'VALUE' => $value,
				'DEF'   => $i === 0 ? 'Y' : 'N',
				'SORT'  => ($i + 1) * 100,
			];
			$i++;
		}

		$enum->SetEnumValues($fieldId, $items);
	}

	/**
	 * Несколько тестовых записей на одну сделку
	 * ID сделки берём из опции (можно поправить перед установкой), по умолчанию 1
	 */
	private function addDemoData($hlblockId)
	{
		$dealId = (int)Option::get($this->MODULE_ID, 'demo_deal_id', 1);

		$hlblock = HighloadBlockTable::getById($hlblockId)->fetch();
		$entity  = HighloadBlockTable::compileEntity($hlblock);
		$dataClass = $entity->getDataClass();

		$typeEnum = $this->getTypeEnumMap();

		$demo = [
			['Роутер Keenetic Giga',     'Роутер'],
			['Ноутбук Lenovo ThinkPad',  'Ноутбук'],
			['Монитор Dell U2720Q',      'Монитор'],
			['Роутер MikroTik hAP ac2',  'Роутер'],
			['Ноутбук MacBook Pro 14',   'Ноутбук'],
		];

		foreach ($demo as $item)
		{
			[$name, $typeValue] = $item;

			$dataClass::add([
				'UF_NAME'         => $name,
				'UF_TYPE'         => $typeEnum[$typeValue] ?? null,
				'UF_CREATED_TIME' => new DateTime(),
				'UF_DEAL_ID'      => $dealId,
			]);
		}
	}

	/** Карта "значение enum => ID" для поля UF_TYPE */
	private function getTypeEnumMap()
	{
		$map = [];
		$res = CUserFieldEnum::GetList([], [
			'USER_FIELD_ID' => $this->typeFieldId,
		]);

		while ($row = $res->GetNext())
		{
			$map[$row['VALUE']] = (int)$row['ID'];
		}

		return $map;
	}
}
