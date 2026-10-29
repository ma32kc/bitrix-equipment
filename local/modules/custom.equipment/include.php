<?php

use Bitrix\Main\Loader;

Loader::registerAutoLoadClasses('custom.equipment', [
	'Custom\\Equipment\\Helper'               => 'lib/helper.php',
	'Custom\\Equipment\\Controller\\Equipment' => 'lib/controller/equipment.php',
	'Custom\\Equipment\\Integration\\CrmTabs'  => 'lib/integration/crmtabs.php',
]);
