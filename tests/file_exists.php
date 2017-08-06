<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/6
 * Time: 13:32
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));
$file = HOME."/cache/signal/94362";
var_dump(file_exists($file));