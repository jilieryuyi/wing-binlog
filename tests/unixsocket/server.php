<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/10/21
 * Time: 15:53
 */



/*
$socket = socket_create(AF_UNIX, SOCK_STREAM, IPPROTO_IP);
$sp     = __DIR__."/unix_socket.socket";

register_shutdown_function(function() use ($sp){
	unlink($sp);
});

$bind   = socket_bind($socket, $sp);
$listen = socket_listen($socket);
$client = socket_accept($socket);
$msg    = "welcome to unix socket";

while ($recv = socket_read($client, 10240)) {
	echo $recv,"\r\n";
}
//echo socket_read($client, 10240);

socket_write($client, $msg, strlen($msg));
socket_close($socket);
*/

$socket = socket_create(AF_UNIX, SOCK_DGRAM, IPPROTO_IP);
$sp     = __DIR__."/unix_socket.socket";

if (file_exists($sp)) unlink($sp);

register_shutdown_function(function() use ($sp){
	unlink($sp);
});

$bind   = socket_bind($socket, $sp);
$msg    = "welcome to unix socket";

socket_set_option($socket,SOL_SOCKET,SO_RCVBUF,1024*1024);

$count = 1;
while ($n = socket_recvfrom($socket, $recv, 10240, 0, $from)) {
	echo $count,"=>",$recv,"\r\n";
	$count++;
}

socket_close($socket);


