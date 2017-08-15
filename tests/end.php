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

var_dump(is_env("windows"));