<?php

namespace Avantis\Api\Controllers;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class BaseController extends \IRestService
{
    const PREFIX_METHOD = 'custom'; // TODO поменять на свое
	const MODULE_ID = 'avantis.api';

    protected static function isAccess(\CRestServer $server)
    {
        $availableScopes = $server->getAuthData()['scopes'] ?: '';
        $availableScopes = array_filter(array_unique(explode(',', $availableScopes)), function ($scope) {
            return !empty($scope);
        });

        if (!$availableScopes) {
            throw new \Exception(Loc::getMessage('AVANTIS_ACCESS_NOT_ALLOWED'));
        }

        $scopes = self::getScopesByMethod($server->getMethod());

        if (count(array_intersect($scopes, $availableScopes)) < 1) {
            throw new \Exception(Loc::getMessage('AVANTIS_ACCESS_NOT_ALLOWED'));
        }

        if(Option::get(self::MODULE_ID, 'DISABLE_ROUTE_'.ToUpper(str_replace('.', '_', $server->getMethod())), 'N') === 'Y') {
			throw new \Exception(Loc::getMessage('AVANTIS_ACCESS_NOT_ALLOWED'));
		}
    }

    protected static function getScopesByMethod($method)
    {
        $scopes = [];
        $method = (string)$method;
        $brokenMethod = explode('.', $method);

        if ($brokenMethod[0] === self::PREFIX_METHOD) {
            unset($brokenMethod[0]);
        }

        foreach (array_values($brokenMethod) as $k => $v) {
            $scopes[$k] = implode('.', array_slice($brokenMethod, 0, $k + 1));
        }

        return $scopes;
    }
}

