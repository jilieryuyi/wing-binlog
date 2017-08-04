<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/30
 * Time: 08:22
 */
include __DIR__."/../vendor/autoload.php";

define("__APP_DIR__", dirname(__DIR__));

\Seals\Library\Context::instance()->zookeeperInit();
echo \Seals\Web\Logic\Server::serversNum();