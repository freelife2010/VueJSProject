<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 30.12.15
 * Time: 12:01
 */

namespace App\Helpers;


use Log;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class APILogger
{
    public static function log($data, $type)
    {
        $handler = new RotatingFileHandler(storage_path().'/logs/api.log',0,Logger::DEBUG);

        Log::getMonolog()->pushHandler($handler);

        Log::debug($data, [$type]);
    }
}