<?php

namespace Avantis\Api\Controllers;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Avantis\Api\Helper\Result;
use Avantis\Api\Helper\Logger;

Loc::loadMessages(__FILE__);

class OrderController extends BaseController
{
    /**
     * @param $params
     * @param $start
     * @param \CRestServer $server
     * @return array
     * @throws \Exception
     */
    public static function update($params, $nav, $server)
    {
        $start = microtime(true);

        $result = new Result();
        $request = Application::getInstance()->getContext()->getRequest();

        \Bitrix\Main\DB\Connection::startTransaction();

        try {
            $body = file_get_contents('php://input');
            $inputParams = json_decode($body, true);

            if (!is_array($inputParams)) {
                $inputParams = [];
            }

            self::isAccess($server);


            /*if (!Loader::includeModule('rdn.exchange')) { // TODO модуль с менеджерами
                $result->addError(new Error(Loc::getMessage('RDN_MODULE_NOT_INSTALLED', [
                    '#MODULE#' => 'rdn.exchange'
                ])));
            }*/

            if ($result->isSuccess()) {
                /*$r = OrderManager::updateStatusFromCrm($inputParams['OrderNumberCRM'], $inputParams['OrderStatusCrm']);

                if (!$r->isSuccess()) {
                    $result->setErrorCollection($r->getErrorCollection());
                } else {
                    $resultData = $r->getData();
                    if ($resultData['ID']) {
                        $result->set('OrderId', $resultData['ID']);
                    }
                }*/
                $result->add($inputParams);
            }
            
        } catch (\Exception $e) {
            $result->addError(new Error($e->getMessage()));
        } catch (\Error $error) {
            $result->addError(new Error($error->getMessage()));
        }

        if ($result->isSuccess()) {
            \Bitrix\Main\DB\Connection::commitTransaction();
        } else {
            \Bitrix\Main\DB\Connection::rollbackTransaction();
        }

        $logData = [
            'HEADERS' => apache_request_headers(),
            'BODY' => $body
        ];

        $finish = microtime(true);

        $processTime = intval(($finish - $start) * 1000);

        Logger::add($_SERVER['REQUEST_URI'], $request->getRequestMethod(), $logData, $result->getResponse(), $start, $processTime);

        return $result->getResponse();
    }

    /**
     * @param $params
     * @param $start
     * @param \CRestServer $server
     * @return array
     * @throws \Exception
     */
    public static function create($params, $nav, $server)
    {
        $start = microtime(true);

        $result = new Result();
        $request = Application::getInstance()->getContext()->getRequest();

        \Bitrix\Main\DB\Connection::startTransaction();

        try {
            $body = file_get_contents('php://input');
            $inputParams = json_decode($body, true);

            if (!is_array($inputParams)) {
                $inputParams = [];
            }

            self::isAccess($server);


            /*if (!Loader::includeModule('rdn.exchange')) { // TODO модуль с менеджерами
                $result->addError(new Error(Loc::getMessage('RDN_MODULE_NOT_INSTALLED', [
                    '#MODULE#' => 'rdn.exchange'
                ])));
            }*/

            if ($result->isSuccess()) {
                /*$r = OrderManager::updateStatusFromCrm($inputParams['OrderNumberCRM'], $inputParams['OrderStatusCrm']);

                if (!$r->isSuccess()) {
                    $result->setErrorCollection($r->getErrorCollection());
                } else {
                    $resultData = $r->getData();
                    if ($resultData['ID']) {
                        $result->set('OrderId', $resultData['ID']);
                    }
                }*/
                $result->add($inputParams);
            }
            
        } catch (\Exception $e) {
            $result->addError(new Error($e->getMessage()));
        } catch (\Error $error) {
            $result->addError(new Error($error->getMessage()));
        }

        if ($result->isSuccess()) {
            \Bitrix\Main\DB\Connection::commitTransaction();
        } else {
            \Bitrix\Main\DB\Connection::rollbackTransaction();
        }

        $logData = [
            'HEADERS' => apache_request_headers(),
            'BODY' => $body
        ];

        $finish = microtime(true);

        $processTime = intval(($finish - $start) * 1000);

        Logger::add($_SERVER['REQUEST_URI'], $request->getRequestMethod(), $logData, $result->getResponse(), $start, $processTime);

        return $result->getResponse();
    }
}

