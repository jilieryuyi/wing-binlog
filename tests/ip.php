<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/4/1
 * Time: 09:25
 */
define("__APP_DIR__", dirname(__DIR__));
include __DIR__."/../vendor/autoload.php";

$command = new \Seals\Library\Command("ifconfig");
$res = $command->run();

echo $res;

preg_match_all("/[\d]{1,3}.[\d]{1,3}.[\d]{1,3}.[\d]{1,3}/",$res,$m);
var_dump($m);