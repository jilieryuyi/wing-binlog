<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 07:23
 */
include_once __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));
define("WING_DEBUG", true);

$msg = "GET / HTTP/1.1
Host: 127.0.0.1:9998
Connection: Upgrade
Pragma: no-cache
Cache-Control: no-cache
Upgrade: websocket
Origin: file://
Sec-WebSocket-Version: 13
User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/59.0.3071.115 Safari/537.36
Accept-Encoding: gzip, deflate, br
Accept-Language: zh-CN,zh;q=0.8,en;q=0.6
Sec-WebSocket-Key: iAjgWv+hsddklHhTRd8lPg==
Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits";

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
$con=socket_connect($socket,'127.0.0.1',9998);
if(!$con){socket_close($socket);exit;}
echo "Link\n";

socket_write($socket, $msg);

socket_write($socket, \Wing\Net\WebSocket::encode("hello"));
//while($con)
{
//    $hear=socket_read($socket,1024);
//    $hear = \Wing\Net\WebSocket::decode($hear);
//    echo $hear;
//    //$words=fgets(STDIN);
//    socket_write($socket,$hear);
    //if($hear=="bye\r\n"){break;}
}
socket_shutdown($socket);
socket_close($socket);