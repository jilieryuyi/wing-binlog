<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/7
 * Time: 07:20
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

$client = new \Wing\Net\WsClient("127.0.0.1", 9998, "/");
$client->send('hello');