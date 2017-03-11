<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/19
 * Time: 09:06
 */
include __DIR__."/../vendor/autoload.php";
define("__APP_DIR__",dirname(__DIR__));
$sql = 'show tables;';
$data = \Seals\Library\Context::instance()->activity_pdo->getTables();

//$pdo = new \Seals\Library\PDO("root","123456","127.0.0.1","ylb_activity",3306);
//$data = $pdo->getTables();
var_dump($data);