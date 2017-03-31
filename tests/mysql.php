<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/31
 * Time: 11:02
 */
define("__APP_DIR__", dirname(__DIR__));
include __DIR__."/../vendor/autoload.php";

$command = new \Seals\Library\Command("ps aux | grep mysqld");
$res = $command->run();

echo $res,"\r\n";