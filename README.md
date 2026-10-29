# Оборудование

Код в `local/`. Модуль `custom.equipment`, компонент `custom:equipment.list`,
вкладка в смарт-процессе и счётчик в карточке сделки

## Поставить

1. Скопировать `local/` в корень портала
2. Если свой `init.php` уже есть - не затирать, добавить из нашего файл регестрацию события
3. Установить модуль `custom.equipment` (локальные решения / список модулей)
   Создастся HL `Equipment`, поля и несколько тестовых записей

Демо-записи цепляются к сделке с ID из опции (по умолчанию 1):

```php
\Bitrix\Main\Config\Option::set('custom.equipment', 'demo_deal_id', 15);
```

## Смарт-процесс

Создать в CRM смарт-процесс со связью со сделками, завести сделку и элемент СП
ID типа сущности взять из URL карточки (`entityTypeId`) и прописать:

```php
\Bitrix\Main\Config\Option::set('custom.equipment', 'sp_entity_type_id', 1032);
```

Без этого вкладка не появится

## Что где

- список: `local/components/custom/equipment.list/` (`main.ui.grid` + `main.ui.filter`)
- вкладка: событие `onEntityDetailsTabsInitialized` в `lib/integration/crmtabs.php`,
  контент грузится через `local/ajax/equipment_tab.php`
- счётчик в сделке: JS `local/js/custom/deal_equipment_count/`, данные из
  `custom:equipment.Equipment.getCount`. Для user ID 5 поле подсвечивается
  Само поле только для отображения

## Как подключается скрипт счётчика

1. `local/php_interface/init.php` подключает модуль и вешает обработчик
   `crm:onEntityDetailsTabsInitialized`
2. В `lib/integration/crmtabs.php` для карточки сделки вызывается
   `Extension::load('custom.deal_equipment_count')`
3. Extension из `local/js/custom/deal_equipment_count/` грузит `script.js` + css
4. JS берёт dealId из URL, дергает `custom:equipment.Equipment.getCount`
   и рисует блок под полем Клиент

## Почему так

HL и поля создаю при установке модуля через ORM - чтобы не кликать руками
В списке фильтр/сортировка/пейджинг на стороне БД, не в PHP
Вкладку и счётчик повесил на одно CRM-событие: и сделка, и СП идут через entity editor
Счётчик не UF-полем, а JS + контроллер - нужен readonly-агрегат из HL под "Клиентом"
и подсветка для конкретного пользователя
