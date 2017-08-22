<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/16
 * Time: 21:47
 */
define("HOME", dirname(__DIR__));
echo HOME."/services/websocket stop";
exec(HOME."/services/tcp stop");
