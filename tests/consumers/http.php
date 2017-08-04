<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/12
 * Time: 07:40
 * 一个简单的http消费端接收测试
 */
set_time_limit(0);
ob_implicit_flush();
$socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
socket_bind( $socket, '127.0.0.1', 9998 );
socket_listen($socket);

while (1) {
    $acpt = socket_accept($socket);
    $hear = socket_read($acpt,10240);
    if ($hear) {

        echo $hear,"\r\n\r\n";
        $response_content   = "1";
        $headers            = [
            "HTTP/1.1 200 OK",
            "Connection: Close",
            "Server: wing-binlog-http-test",
            "Date: " . gmdate("D,d M Y H:m:s")." GMT",
            "Content-Type: text/html",
            "Content-Length: " . strlen($response_content)
        ];

        socket_write($acpt,implode("\r\n",$headers)."\r\n\r\n".$response_content);
    }
    socket_close($acpt);
    sleep(1);
}
socket_close($socket);