<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\Filter\Options as FilterOptions;
use Bitrix\Main\UI\PageNavigation;
use Custom\Equipment\Helper;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

/**
 * Список оборудования по сделке: таблица на main.ui.grid + фильтр main.ui.filter
 * Фильтрация, сортировка и постраничка считаются на стороне БД через ORM HL-блока
 */
class CustomEquipmentListComponent extends CBitrixComponent
{
	public function onPrepareComponentParams($params)
	{
		$params['DEAL_ID']      = isset($params['DEAL_ID']) ? (int)$params['DEAL_ID'] : 0;
		$params['GRID_ID']      = trim((string)($params['GRID_ID'] ?? 'custom_equipment_grid'));
		$params['FILTER_ID']    = trim((string)($params['FILTER_ID'] ?? 'custom_equipment_filter'));
		$params['PAGE_SIZE']    = (int)($params['PAGE_SIZE'] ?? 20) ?: 20;

		return $params;
	}

	public function executeComponent()
	{
		if (!Loader::includeModule('custom.equipment'))
		{
			ShowError(Loc::getMessage('CUSTOM_EQUIPMENT_LIST_NO_MODULE'));
			return;
		}

		$dealId = (int)$this->arParams['DEAL_ID'];

		// Без сделки список пустой - показывать "всё оборудование" на вкладке сделки не нужно
		if ($dealId <= 0)
		{
			$this->arResult['ROWS']    = [];
			$this->arResult['COLUMNS'] = $this->getColumns();
			$this->arResult['TOTAL']   = 0;
			$this->includeComponentTemplate();
			return;
		}

		$this->arResult['FILTER']        = $this->getFilterFields();
		$this->arResult['COLUMNS']       = $this->getColumns();

		[$rows, $total] = $this->getRows($dealId);

		$this->arResult['ROWS']  = $rows;
		$this->arResult['TOTAL'] = $total;

		$this->includeComponentTemplate();
	}

	/** Поля фильтра: название (строка) и тип (список) */
	private function getFilterFields(): array
	{
		return [
			[
				'id'   => 'UF_NAME',
				'name' => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_COL_NAME'),
				'type' => 'string',
			],
			[
				'id'    => 'UF_TYPE',
				'name'  => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_COL_TYPE'),
				'type'  => 'list',
				'items' => Helper::getTypeItems(),
				'params' => ['multiple' => 'Y'],
			],
		];
	}

	/** Колонки грида */
	private function getColumns(): array
	{
		return [
			['id' => 'UF_NAME',         'name' => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_COL_NAME'), 'sort' => 'UF_NAME',         'default' => true],
			['id' => 'UF_TYPE',         'name' => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_COL_TYPE'), 'sort' => 'UF_TYPE',         'default' => true],
			['id' => 'UF_CREATED_TIME', 'name' => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_COL_DATE'), 'sort' => 'UF_CREATED_TIME', 'default' => true],
			['id' => 'UF_DEAL_ID',      'name' => Loc::getMessage('CUSTOM_EQUIPMENT_LIST_COL_DEAL'), 'sort' => 'UF_DEAL_ID',      'default' => true],
		];
	}

	/**
	 * Выборка строк с учётом фильтра, сортировки и постранички
	 *
	 * @return array{0: array, 1: int} строки для грида и общее количество
	 */
	private function getRows(int $dealId): array
	{
		$dataClass = Helper::getDataClass();
		$typeItems = Helper::getTypeItems();

		$filter = ['=UF_DEAL_ID' => $dealId];
		$filter += $this->getUserFilter();

		// Сортировка из настроек грида (по умолчанию - свежие сверху)
		$gridOptions = new GridOptions($this->arParams['GRID_ID']);
		$sorting = $gridOptions->getSorting(['sort' => ['UF_CREATED_TIME' => 'DESC']]);
		$order = $sorting['sort'] ?: ['UF_CREATED_TIME' => 'DESC'];

		// Постраничка через стандартный PageNavigation
		$nav = new PageNavigation($this->arParams['GRID_ID'] . '_nav');
		$nav->allowAllRecords(true)
			->setPageSize($this->arParams['PAGE_SIZE'])
			->initFromUri();

		$total = (int)$dataClass::getCount($filter);

		$query = $dataClass::getList([
			'select' => ['ID', 'UF_NAME', 'UF_TYPE', 'UF_CREATED_TIME', 'UF_DEAL_ID'],
			'filter' => $filter,
			'order'  => $order,
			'limit'  => $nav->getLimit(),
			'offset' => $nav->getOffset(),
		]);

		$rows = [];
		while ($item = $query->fetch())
		{
			$created = $item['UF_CREATED_TIME'] instanceof \Bitrix\Main\Type\DateTime
				? $item['UF_CREATED_TIME']->toString()
				: (string)$item['UF_CREATED_TIME'];

			$rows[] = [
				'id'      => $item['ID'],
				'columns' => [
					'UF_NAME'         => htmlspecialcharsbx($item['UF_NAME']),
					'UF_TYPE'         => htmlspecialcharsbx($typeItems[$item['UF_TYPE']] ?? ''),
					'UF_CREATED_TIME' => $created,
					'UF_DEAL_ID'      => (int)$item['UF_DEAL_ID'],
				],
			];
		}

		$nav->setRecordCount($total);
		$this->arResult['NAV_OBJECT'] = $nav;

		return [$rows, $total];
	}

	/** Условия фильтра из main.ui.filter -> ORM-фильтр */
	private function getUserFilter(): array
	{
		$filterOptions = new FilterOptions($this->arParams['FILTER_ID']);
		$data = $filterOptions->getFilter();

		$result = [];

		if (!empty($data['UF_NAME']))
		{
			$result['%UF_NAME'] = $data['UF_NAME'];
		}

		if (!empty($data['UF_TYPE']))
		{
			$result['=UF_TYPE'] = $data['UF_TYPE'];
		}

		return $result;
	}
}
