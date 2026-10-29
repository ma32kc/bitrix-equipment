<?php

namespace Custom\Equipment\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\ActionFilter;
use Custom\Equipment\Helper;

/**
 * Ajax-контроллер оборудования
 * Пока один метод - количество оборудования по сделке (для карточки сделки)
 */
class Equipment extends Controller
{
	public function configureActions()
	{
		return [
			'getCount' => [
				'prefilters' => [
					new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_GET]),
				],
			],
		];
	}

	/**
	 * Количество оборудования, привязанного к сделке
	 *
	 * @param int $dealId
	 * @return array{count:int}
	 */
	public function getCountAction(int $dealId): array
	{
		return ['count' => Helper::countByDeal($dealId)];
	}
}
