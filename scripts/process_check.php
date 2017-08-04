<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/16
 * Time: 14:04
 */
include __DIR__."/../vendor/autoload.php";

define("__APP_DIR__",dirname(__DIR__));

$command = new \Seals\Library\Command("ps aux | grep seals");
$res     = $command->run();

$lines = explode("\n",$res);
$count = 0;

foreach ( $lines as $line ){
    if( strpos($line,"seals >>")!==false)
        $count++;
}

$res = new \Wing\FileSystem\WFile(__APP_DIR__."/seals.pid");

list( , $workers_num,  ) = explode(":",$res->read());

if( $count < 2*$workers_num+1 && $workers_num > 0  ){
    (new \Seals\Library\Command("cd ".__APP_DIR__." && php seals server:restart"))->run();
}
