<?php
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;

$moduleId = 'avantis.api';

require_once($_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $moduleId . '/include.php');

Loc::loadMessages(__FILE__);

if (!Loader::includeModule($moduleId)) {
    return;
}

$arTabs = array(
    array(
        'DIV' => 'SETTINGS',
        'TAB' => Loc::getMessage('AVANTIS_API_PAGE_CONFIG_TAB_TITLE'),
        'ICON' => '',
        'TITLE' => Loc::getMessage('AVANTIS_API_PAGE_CONFIG_TAB_TITLE')
    )
);

$arGroups = array(
    'DISABLE_ROUTE' => array(
        'TITLE' => Loc::getMessage('AVANTIS_API_PAGE_DISABLE_ROUTE_TAB_TITLE'),
        'TAB' => 0
    ),
);

$arRoutes = array_keys(Avantis\Api\Rest\Routes::onRestServiceBuildDescription()['custom']); // TODO изменить на свое

$arOptions = [];

foreach($arRoutes as $key => $route) {
	$arOptions['DISABLE_ROUTE_'.ToUpper(str_replace('.', '_', $route))] = [
		'GROUP' => 'DISABLE_ROUTE',
		'TITLE' => Loc::getMessage('AVANTIS_API_DISABLE_ROUTE_OPTION', ['#ROUTE#' => $route]),
		'TYPE' => 'CHECKBOX',
		'DEFAULT' => 'N',
		'SORT' => '1'.$key.'1',
	];
}

$opt = new Avantis\API\ModuleOptions($moduleId, $arTabs, $arGroups, $arOptions, true);
$opt->ShowHTML();
