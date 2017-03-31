<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/31
 * Time: 11:02
 */
$handle = popen("ps aux | grep mysqld","r");
$res = fgets($handle, 1024);

echo $res;

pclose($handle);