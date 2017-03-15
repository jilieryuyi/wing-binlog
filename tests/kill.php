<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/14
 * Time: 07:49
 */
//posix_kill($argv[1],SIGQUIT);
include __DIR__."/../vendor/autoload.php";

$res = (new \Seals\Library\Command("ps aux | grep master:start"))->run();
$lines = explode("\n",$res);
var_dump($lines);
foreach ($lines as $line){
    preg_match("/[\d]+/",$line,$match);
    var_dump($match);
    if (intval($match[0]) > 0)
    (new \Seals\Library\Command("kill ".$match[0]))->run();
}