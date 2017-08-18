<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 06:50
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

$client = new \Wing\Net\WsClient("127.0.0.1", 9990, "/");
while (1) {
    $client->send(json_encode([
        "event_index"=>0,
        "event"=>1
    ])."\r\n\r\n\r\n");
}

