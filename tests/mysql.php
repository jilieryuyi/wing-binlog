<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/31
 * Time: 11:02
 */
define("__APP_DIR__", dirname(__DIR__));
include __DIR__."/../vendor/autoload.php";

$command = new \Seals\Library\Command("ps aux | grep /usr/local/mysql/bin/mysqld");
$res     = $command->run();

$temp = explode("\n", $res);
var_dump($temp);

if (count($temp)!= 4) {
    $handle = popen("/bin/sh -c \"/usr/local/mysql/bin/mysqld_safe\" >> /tmp/mysql_restart.log&","r");
    if ($handle) {
        pclose($handle);
    }
}

echo $res,"\r\n";