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
$res = $command->run();

echo "==============\r\n";
echo $res;
echo "==============\r\n";

$lines = explode("\n",$res);
var_dump($lines);
$count = 0;
foreach ( $lines as $line ){
    if( strpos($line,"seals >>")!==false)
        $count++;
}

echo __APP_DIR__."/seals.pid","\r\n";

$res = new \Wing\FileSystem\WFile(__APP_DIR__."/seals.pid");

list( $deamon, $workers_num, $debug ) = explode(":",$res->read());
$deamon = $deamon == 1;
$debug  = $debug == 1;


echo "===>count=",$count,"==>workers_num=",$workers_num,"\r\n";
//cd /alidata/www/seals-analysis && php scripts/process_check.php >> /alidata/www/seals-analysis/process_check.log
if( $count < $workers_num+1 && $workers_num > 0  ){
    (new \Seals\Library\Command("cd /alidata/www/seals-collector && php seals server:restart"))->run();
}
