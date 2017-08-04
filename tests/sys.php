<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/4/1
 * Time: 14:43
 */
define("__APP_DIR__", dirname(__DIR__));
include __DIR__."/../vendor/autoload.php";

var_dump(\Seals\Library\System::getCpuUsage());