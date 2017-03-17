<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/17
 * Time: 13:10
 */
include __DIR__."/../vendor/autoload.php";

define("__APP_DIR__", dirname(__DIR__));
\Seals\Library\Context::instance()->initPdo();
//$a = \Seals\Library\Context::instance()->activity_pdo->query("set @@global.general_log=1");
//var_dump($a);

$g = new \Seals\Library\GeneralLog(\Seals\Library\Context::instance()->activity_pdo);

var_dump($g->getLogPath());
var_dump($g->isOpen());
var_dump($g->open());
var_dump($g->isOpen());