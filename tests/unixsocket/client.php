<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/10/21
 * Time: 16:08
 */

/* create a socket */
$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
$sp     = __DIR__."/unix_socket.socket";
$msg    = "hello";

socket_connect($socket, $sp);
socket_write($socket, $msg, strlen($msg));

echo socket_read($socket, 10240);
socket_close($socket);