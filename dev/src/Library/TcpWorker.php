<?php namespace Wing\Library;
use Wing\FileSystem\WDir;
use Wing\Net\Tcp;
use Wing\Net\WebSocket;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 07:45
 */
class TcpWorker extends BaseWorker
{
    private $clients = [];
    private $process = [];
    public function __construct()
    {
        $dir = HOME."/cache/tcp";
        (new WDir($dir))->mkdir();
    }

    /**
     * @param Tcp $tcp
     */
    private function broadcast($tcp)
    {
        $pid = pcntl_fork();
        if ($pid > 0) {
            foreach ($this->process as $_pid) {
                (new Signal($_pid))->kill();
            }
            $this->process = [];
            $this->process[] = $pid;
            echo "tcp广播子进程";
            var_dump($this->process);
            return;
        }

        set_process_title("wing php >> websocket broadcast process");
        $current_process_id = get_current_processid();
        $signal = new Signal($current_process_id);

        $run_count = 0;
        $cc        = intval(1000000/self::USLEEP);
        while (1) {
            if ($run_count%$cc == 0) {
                if ($signal->checkStopSignal()) {
                    echo $current_process_id,"tcp广播进程收到终止信息号\r\n";
                    exit;
                }
                $run_count = 0;
            }
            //广播消息
            $path[] = HOME . "/cache/websocket/*";
            //var_dump($this->clients);
            while (count($path) != 0) {
                $v = array_shift($path);
                foreach (glob($v) as $item) {
                    if (is_file($item)) {
                        $content = file_get_contents($item);
                        //$client, $buffer, $data
                        foreach ($this->clients as $w) {
                            echo "发送消息：", $content, "\r\n";
                            $tcp->send($w[0], $content, $w[1]);
                        }
                        unlink($item);
                    }
                }
            }

            $run_count++;
            usleep(self::USLEEP);
        }
    }

    /**
     * @param WebSocket $tcp
     */
//    private function writeNum($clients, $tcp)
//    {
//        $num = count($clients);
//        $file = HOME."/cache/websocket/clients";
////        if (!file_exists($file)) {
////            touch($file);
////        }
////        $handle = fopen($file,"w+");
////        flock($handle, LOCK_EX);
////        fwrite($handle, $num);
////        flock($handle, LOCK_UN);
////        fclose($handle);
//        file_put_contents($file, 1);
//
//        $pid = pcntl_fork();
//        if ($pid > 0) {
//            return;
//        }
//
//        set_process_title("wing php >> websocket broadcast process")
//        while (1) {
//            if (1 == file_get_contents($file)) {
//                echo
//                exit;
//            }
//            $path[] = HOME . "/cache/websocket/*";
//            while (count($path) != 0) {
//                $v = array_shift($path);
//                foreach (glob($v) as $item) {
//                    if (is_file($item)) {
//                        $content = file_get_contents($item);
//                        //$client, $buffer, $data
//                        foreach ($clients as $w) {
//                            $tcp->send($w[1],$w[2], $w[0]);
//                        }
//                        unlink($item);
//                    }
//                }
//            }
//
//            usleep(self::USLEEP);
//        }
//    }

    public function start()
    {
        $process_id = pcntl_fork();

        if ($process_id < 0) {
            echo "fork a process fail\r\n";
            exit;
        }

        if ($process_id > 0) {
            return $process_id;
        }

        $tcp     = new \Wing\Net\Tcp();
        //$clients = $this->clients;

        //$is_start = false;
//        pcntl_signal(SIGALRM, function($signal) use($clients){
//            echo "时钟信号\r\n";
//            var_dump($clients);
//            pcntl_alarm(1);
//        }, true);

        $tcp->on(\Wing\Net\Tcp::ON_CONNECT, function($client, $buffer) use($tcp) {
            //var_dump(func_get_args());
            $this->clients[intval($client)] = [$buffer, $client];
            $this->broadcast($tcp);
            //$this->writeNum($clients, $tcp);
//            if (!$is_start)pcntl_alarm(1);
//            $is_start = true;
        });

        $tcp->on(\Wing\Net\Tcp::ON_RECEIVE, function($client, $buffer, $recv_msg) use($tcp){
            //var_dump(func_get_args());

//            if (0 === strpos($recv_msg, 'GET')) {
//              //  echo "收到握手消息：",($recv_msg),"\r\n\r\n";
//                //握手消息
//                $tcp->handshake($buffer, $recv_msg, $client);//, $recv_msg), $client );
//                return;
//            }

           // echo "收到的消息：",\Wing\Net\WebSocket::decode($recv_msg),"\r\n\r\n";
            //一般的消息响应
            //$tcp->send($buffer, "1239999999999", $client);

        });

        $tcp->on(Tcp::ON_CLOSE, function($client, $buffer) use($tcp){
            unset($this->clients[intval($client)]);
            $this->broadcast($tcp);
            //$this->writeNum($clients, $tcp);
        });

        $tcp->on(Tcp::ON_ERROR,function($client, $buffer, $error) use($tcp){
            unset($this->clients[intval($client)]);
            $this->broadcast($tcp);
        });
        //pcntl_alarm(1);
        $tcp->start();
        return 0;
    }
}