<?php

use Bitrix\Main\Loader;

include_once __DIR__ . '/constants.php';


$arClasses = [
    'Avantis\\DealerManager' => '/local/php_interface/classes/dealerManager.php',
//    'Avantis\\DealerHelper;' => '/local/php_interface/classes/dealerHelper.php',
    'Avantis\\DealerBindsTable' => '/local/php_interface/classes/entities/user_buyer_contact_company.php',
    'Avantis\\ContragentDataTmpTable' => '/local/php_interface/classes/entities/tmp_contragent_data.php',

    'Avantis\\DevHelper' => '/local/php_interface/classes/devHelper.php', // todo delete on prod
];


Loader::registerAutoLoadClasses( null, $arClasses);

