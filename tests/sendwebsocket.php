<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/6
 * Time: 12:09
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

$s = new \Wing\Subscribe\WebSocket();
$s->onchange("11", "22", "33");