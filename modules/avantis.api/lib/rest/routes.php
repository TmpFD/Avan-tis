<?php
namespace Avantis\Api\Rest;

use Avantis\Api\Controllers;

class Routes
{
    public static function onRestServiceBuildDescription()
    {
        $result = array(
            'custom' => array(
                'custom.order.update' => array(Controllers\OrderController::class, 'update'),
                'custom.order.create' => array(Controllers\OrderController::class, 'create'),

            )
        );

        return $result;
    }
}
