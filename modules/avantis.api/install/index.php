<?php defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class avantis_api extends CModule  
{
    var $MODULE_ID = 'avantis.api';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $PARTNER_NAME;
    var $PARTNER_URI;

    static $installDirs = [];
    static $events = [
        [
            'module' => 'rest',
            'event' => 'OnRestServiceBuildDescription',
            'class' => Avantis\Api\Rest\Routes::class, 
            'method' => 'onRestServiceBuildDescription',
            'sort' => 100
        ]
    ];
    static $entities = [];
    static $agents = [];

    function __construct()
    {
        $arModuleVersion = [];

        include(__DIR__ . '/version.php');

        if (is_array($arModuleVersion) && array_key_exists('VERSION', $arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('AVANTIS_API_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('AVANTIS_API_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('AVANTIS_API_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('AVANTIS_API_PARTNER_URI');
    }

    function installFiles()
    {
        $docRoot = Application::getInstance()->getContext()->getServer()->getDocumentRoot();
        foreach (self::$installDirs as $dir) {
            CopyDirFiles($dir['from'], $docRoot . $dir['to'], true, true);
        }
        return true;
    }

    function unInstallFiles()
    {
        foreach (self::$installDirs as $dir) {
            DeleteDirFilesEx($dir['to'] . '/' . $this->MODULE_ID);
        }
        return true;
    }

    public function installDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            $connection = Application::getInstance()->getConnection();
            foreach (self::$entities as $entity)
            {
                if (!$connection->isTableExists($entity['class']::getTableName()))
                {
                    $entity['class']::getEntity()->createDbTable();
                    if (array_key_exists('indexes', $entity))
                    {
                        $connection = Application::getConnection();
                        foreach ($entity['indexes'] as $index)
                        {
                            $connection->createIndex($entity['class']::getTableName(), $index['name'], $index['fields']);
                        }
                    }
                }
            }
        }
    }

    public function uninstallDB()
    {
        if (Loader::includeModule($this->MODULE_ID))
        {
            $connection = Application::getInstance()->getConnection();
            foreach (self::$entities as $entity)
            {
                if ($connection->isTableExists($entity['class']::getTableName()))
                {
                    $connection->dropTable($entity['class']::getTableName());
                }
            }
        }
    }

    public function registerEvents()
    {
        $eventManager = EventManager::getInstance();

        foreach (self::$events as $event) {
            $eventManager->registerEventHandler(
                $event['module'],
                $event['event'],
                $this->MODULE_ID,
                $event['class'],
                $event['method'],
                $event['sort']
            );
        }
    }

    public function unRegisterEvents()
    {
        $eventManager = EventManager::getInstance();

        foreach (self::$events as $event) {
            $eventManager->unRegisterEventHandler(
                $event['module'],
                $event['event'],
                $this->MODULE_ID,
                $event['class'],
                $event['method']
            );
        }
    }

    public function addAgents()
    {
        foreach (self::$agents as $agent) {
            $now = new \Bitrix\Main\Type\DateTime();
            $now->add($agent['FIRST_RUN_MODIFICATOR'] ?: '10 minutes');
            $agent['MODULE_ID'] = $this->MODULE_ID;
            $agent['NEXT_EXEC'] = $now;
            \CAgent::Add($agent);
        }
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        $this->installDB();
        $this->installFiles();
        $this->registerEvents();
        $this->addAgents();
    }

    public function DoUninstall()
    {
        $this->unInstallFiles();
        $this->uninstallDB();
        $this->unRegisterEvents();
        \CAgent::RemoveModuleAgents($this->MODULE_ID);

        ModuleManager::unregisterModule($this->MODULE_ID);
    }
}
