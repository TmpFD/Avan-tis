<?php
namespace Avantis;

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Crm;
use Avantis\DealerBindsTable;


class DevHelper
{

    public static function addTmpData($data)
    {

    }

    public static function getTmpData($companyXml)
    {

    }

    public static function createTable($tableEntityName)
    {
        $db = Application::getConnection();

        $entity = $tableEntityName::getEntity();
        if(!$db->isTableExists(($entity->getDBTableName()))){
            $entity->createDbTable();
        }
    }

    public static function deleteTable($tableEntityName)
    {
        $db = Application::getConnection();

        $entity = $tableEntityName::getEntity();
        if($db->isTableExists(($entity->getDBTableName()))){
            $db->dropTable($entity->getDBTableName());
        }
    }

}