<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/17
 * Time: 07:52
 *
 * windows兼容
 */

if (!defined("SIGINT")) {
    define("SIGINT", 1);
}
if (!defined("SIGUSR1")) {
    define("SIGUSR1", 2);
}

if (!defined("SIGUSR2")) {
    define("SIGUSR2", 3);
}
if (!defined("SIGPIPE")) {
    define("SIGPIPE", 4);
}
if (!defined("SIG_IGN")) {
    define("SIG_IGN", 5);
}

if (!function_exists("pcntl_signal")) {
    function pcntl_signal($a=null,$b=null,$c=null,$d=null){}
}
if (!function_exists("posix_kill")) {
    function posix_kill($a=null, $b = null, $c = null){}
}
if (!function_exists("pcntl_signal_dispatch")) {
    function pcntl_signal_dispatch($a=null, $b = null, $c = null){}
}
if (!function_exists("pcntl_wait")) {
    function pcntl_wait($a=null, $b = null, $c = null){ return 0;}
}
