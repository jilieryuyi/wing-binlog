<?php
/**
 * end.php
 * User: huangxiaoan
 * Created: 2017/8/15 18:15
 * Email: huangxiaoan@xunlei.com
 */
include __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));
define("WING_DEBUG",  true);


	$command = HOME."/services/tcp.exe start ".$port;

popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/tcp.log&","r");