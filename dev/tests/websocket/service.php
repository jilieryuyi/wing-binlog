<?php
/**
 * @author yuyi
 * @created 2016/8/13 19:45
 * @email 297341015@qq.com
 * @websocket协议的简单实现
 */
include_once  "WebSocket.php";
$server = new wing_select_server(
    "0.0.0.0" ,  //ip 默认值 0.0.0.0
    9998 ,       //端口 默认值 6998
    20000,       //最大连接数限制 默认值 1000
    1000,        //发送/接收超时时间 默认值 0 不限
    3000         //未活动超时时间 默认值 0 不限
);

$server->on( "onreceive" , function( $client , $recv_msg ) {

    if (0 === strpos($recv_msg, 'GET')) {
        echo "收到来自",$client->socket,"的消息：",($recv_msg),"\r\n\r\n";
        //握手消息
        $client->send( WebSocket::handshake($recv_msg) );
        return;
    }

    echo "收到来自",$client->socket,"的消息：",WebSocket::decode($recv_msg),"\r\n\r\n";
    //一般的消息响应
    $client->send(WebSocket::encode("1239999999999"));
});

$server->on( "onsend" , function( $client , $send_status ){
    echo "\r\n\r\n",$client->socket;
    if( $send_status )
        echo "发送成功";
    else
        echo "发送失败";
    echo "\r\n";
});
$server->on( "onconnect",function( $client ) {
    echo $client->socket,"连接进来了\r\n";
});

$server->on( "onclose",function( $client ) {
    echo $client->socket,"掉线啦\r\n";
});

$server->on( "onerror", function( $client, $error_code, $error_msg ) {
    echo $client->socket,"发生了错误，错误码",$error_code,"，错误内容：", $error_msg, "\r\n";
});

$server->on( "ontimeout" , function( $client ) {
    echo $client->socket,"好长时间没活动啦\r\n";
});

$server->start();