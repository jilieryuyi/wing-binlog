<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 06:50
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

$tcp = new \Wing\Net\Tcp();
$tcp->on(\Wing\Net\Tcp::ON_CONNECT, function() {
    var_dump(func_get_args());
});


function send($buffer, $data, $client)
{
    global $tcp;
    if ($buffer) {
        $success = event_buffer_write($buffer,$data);
    }
    else
        $success = $tcp->sendSocket($client, $data);
    return $success;
}


$tcp->on(\Wing\Net\Tcp::ON_RECEIVE, function($client, $buffer, $recv_msg){
    var_dump(func_get_args());

    if (0 === strpos($recv_msg, 'GET')) {
        echo "收到握手消息：",($recv_msg),"\r\n\r\n";
        //握手消息
        send($buffer, \Wing\Net\WebSocket::handshake($recv_msg), $client );
        return;
    }

    echo "收到的消息：",\Wing\Net\WebSocket::decode($recv_msg),"\r\n\r\n";
    //一般的消息响应
    send($buffer, \Wing\Net\WebSocket::encode("1239999999999"), $client);
});

$tcp->start();