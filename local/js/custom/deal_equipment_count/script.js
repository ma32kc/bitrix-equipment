;(function () {
	'use strict';

	// ID пользователя, для которого поле нужно визуально выделить (по ТЗ - 5)
	var HIGHLIGHT_USER_ID = 5;

	// Сколько раз пытаемся найти блок "Клиент" (карточка рисуется асинхроно)
	var MAX_TRIES = 40;
	var TRY_INTERVAL = 300;

	function getDealId() {
		var m = location.pathname.match(/\/crm\/deal\/details\/(\d+)\//);
		return m ? parseInt(m[1], 10) : 0;
	}

	// Контейнер поля "Клиент" в entity editor
	function findClientField() {
		return document.querySelector('[data-cid="CLIENT"]')
			|| document.querySelector('[data-cid="client"]');
	}

	function renderField(clientField, count) {
		if (document.getElementById('custom-equipment-count')) {
			return;
		}

		var box = document.createElement('div');
		box.id = 'custom-equipment-count';
		box.className = 'custom-equipment-count';

		var title = document.createElement('span');
		title.className = 'custom-equipment-count__title';
		title.textContent = 'Оборудование';

		var value = document.createElement('span');
		value.className = 'custom-equipment-count__value';
		value.textContent = String(count);

		box.appendChild(title);
		box.appendChild(value);

		if (parseInt(BX.message('USER_ID'), 10) === HIGHLIGHT_USER_ID) {
			box.classList.add('custom-equipment-count--highlight');
		}

		// Ставим сразу под полем "Клиент"
		clientField.parentNode.insertBefore(box, clientField.nextSibling);
	}

	function loadCount(dealId, clientField) {
		BX.ajax.runAction('custom:equipment.Equipment.getCount', {
			data: { dealId: dealId },
		}).then(function (response) {
			var count = (response && response.data) ? response.data.count : 0;
			renderField(clientField, count);
		});
	}

	function init() {
		var dealId = getDealId();
		if (!dealId) {
			return;
		}

		var tries = 0;
		var timer = setInterval(function () {
			tries++;
			var clientField = findClientField();

			if (clientField) {
				clearInterval(timer);
				loadCount(dealId, clientField);
			} else if (tries >= MAX_TRIES) {
				clearInterval(timer);
			}
		}, TRY_INTERVAL);
	}

	BX.ready(init);
})();
