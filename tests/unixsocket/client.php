<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/10/21
 * Time: 16:08
 */

/*
$socket = socket_create(AF_UNIX, SOCK_STREAM, 0);
$sp     = __DIR__."/unix_socket.socket";
$msg    = "hello";

socket_connect($socket, $sp);
while(1) {
	socket_write($socket, $msg, strlen($msg));
}

echo socket_read($socket, 10240);
socket_close($socket);
*/

$socket = socket_create(AF_UNIX, SOCK_DGRAM, 0);
$sp     = __DIR__."/unix_socket.socket";
$msg    = "hello";

//socket_connect($socket, $sp);
$count = 0;

socket_set_option($socket,SOL_SOCKET,SO_SNDBUF,1024*1024);


while (1) {
	socket_sendto($socket, $msg, strlen($msg),0, $sp);
	//socket_write($socket, $msg, strlen($msg));
	$count++;
	echo $count,"\r\n";
}

socket_close($socket);