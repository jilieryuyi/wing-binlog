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

$pid = pcntl_fork();
if ($pid > 0) {
    //父进程接收消息
    $count = 0;
    $msg_all = "";
    $split = "\r\n\r\n\r\n";
    while ($msg = socket_read($socket, 10240)) {
        $msg_all.=$msg;
        $temp = explode($split, $msg_all);
        if (count($temp) >= 2) {
            $msg_all = array_pop($temp);
            foreach ($temp as $v) {
                if (!$v) {
                    continue;
                }
                $count++;
                echo $v, "\r\n";
                echo "收到消息次数：", $count, "\r\n\r\n";
            }
        }
        unset($temp);
    }

    echo "连接关闭\r\n";
    socket_shutdown($socket);
    socket_close($socket);

    $start = 0;
    while(1) {
        $status = 0;
        $pid = pcntl_wait($status);
        if ($pid > 0) {
            break;
        }

        if ((time()-$start) > 5) break;
    }

} else {
    //子进程发送心跳包
    while(1) {
        try {
            socket_write($socket, "tick");
            usleep(500000);
        }catch(\Exception $e){
            exit;
        }
    }

}