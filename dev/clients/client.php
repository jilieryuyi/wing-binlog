<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 07:23
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));


$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$con    = socket_connect($socket,'127.0.0.1',9997);

if(!$con) {
    socket_close($socket);
    echo "无法连接服务器\r\n";
    exit;
}

echo "连接成功\n";

//socket_write($socket, $msg);

//socket_write($socket, \Wing\Net\WebSocket::encode("hello"));
while($msg = socket_read($socket,10240))
{
    echo $msg,"\r\n";
}

echo "连接关闭\r\n";
socket_shutdown($socket);
socket_close($socket);