<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/10/21
 * Time: 15:53
 */

$socket = socket_create(AF_UNIX, SOCK_STREAM, IPPROTO_IP);
$sp     = __DIR__."/unix_socket.socket";
$bind   = socket_bind($socket, $sp);
$listen = socket_listen($socket);
$client = socket_accept($socket);
$msg    = "welcome to unix socket";

echo socket_read($client, 10240);

socket_write($client, $msg, strlen($msg));
socket_close($socket);
