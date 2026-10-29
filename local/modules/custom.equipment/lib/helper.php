<?php

namespace Custom\Equipment;

use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;
use Bitrix\Highloadblock\HighloadBlockTable;

/**
 * Доступ к HL-блоку "Оборудование"
 *
 * Собрано в один класс, чтобы не дублировать получение ID блока и
 * скомпилированной ORM-сущности по всему проекту (компонент, контроллер, события)
 */
class Helper
{
	const MODULE_ID    = 'custom.equipment';
	const HLBLOCK_NAME = 'Equipment';

	/** @var string|null Кэш имени класса ORM-сущности в рамках хита */
	private static $dataClass;

	/** @var int Кэш ID HL-блока */
	private static $hlblockId = 0;

	/**
	 * ID HL-блока по имени. 0, если блок не найден
	 */
	public static function getHlblockId(): int
	{
		if (self::$hlblockId)
		{
			return self::$hlblockId;
		}

		Loader::includeModule('highloadblock');

		$row = HighloadBlockTable::getRow([
			'select' => ['ID'],
			'filter' => ['=NAME' => self::HLBLOCK_NAME],
		]);

		self::$hlblockId = $row ? (int)$row['ID'] : 0;

		return self::$hlblockId;
	}

	/**
	 * Имя класса ORM-сущности HL-блока для getList/getCount/add.
	 *
	 * @return string
	 */
	public static function getDataClass(): string
	{
		if (self::$dataClass)
		{
			return self::$dataClass;
		}

		Loader::includeModule('highloadblock');

		$hlblockId = self::getHlblockId();
		if (!$hlblockId)
		{
			throw new \RuntimeException('HL-блок "' . self::HLBLOCK_NAME . '" не найден. Установите модуль ' . self::MODULE_ID . '.');
		}

		$hlblock = HighloadBlockTable::getById($hlblockId)->fetch();
		self::$dataClass = HighloadBlockTable::compileEntity($hlblock)->getDataClass();

		return self::$dataClass;
	}

	/**
	 * Количество единиц оборудования, привязанного к сделке
	 */
	public static function countByDeal(int $dealId): int
	{
		if ($dealId <= 0)
		{
			return 0;
		}

		$dataClass = self::getDataClass();

		return (int)$dataClass::getCount(['=UF_DEAL_ID' => $dealId]);
	}

	/**
	 * Значения списка UF_TYPE в виде [ID => "Текст"]
	 * Нужны и для фильтра (выпадающий список), и для грида (показать текст вместо ID)
	 */
	public static function getTypeItems(): array
	{
		$hlblockId = self::getHlblockId();
		if (!$hlblockId)
		{
			return [];
		}

		$field = \CUserTypeEntity::GetList([], [
			'ENTITY_ID'  => 'HLBLOCK_' . $hlblockId,
			'FIELD_NAME' => 'UF_TYPE',
		])->Fetch();

		if (!$field)
		{
			return [];
		}

		$items = [];
		$res = \CUserFieldEnum::GetList([], ['USER_FIELD_ID' => $field['ID']]);
		while ($row = $res->GetNext())
		{
			$items[$row['ID']] = $row['VALUE'];
		}

		return $items;
	}

	/**
	 * ID типа смарт-процесса, в карточку которого добавляем вкладку
	 * Задаётся опцией модуля - конкретный ID зависит от портала (см. README)
	 */
	public static function getSmartProcessEntityTypeId(): int
	{
		return (int)Option::get(self::MODULE_ID, 'sp_entity_type_id', 0);
	}
}
