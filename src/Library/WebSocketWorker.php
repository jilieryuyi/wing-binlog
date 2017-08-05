<?php namespace Wing\Library;
use Wing\FileSystem\WDir;
use Wing\Net\Tcp;
use Wing\Net\WebSocket;
//use Workerman\Worker;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/5
 * Time: 07:45
 */
class WebSocketWorker extends BaseWorker
{
    private $clients = [];
    private $process = [];
    public function __construct()
    {
        $dir = HOME."/cache/websocket";
        (new WDir($dir))->mkdir();
    }

    private function broadcast3()
    {
//        $pid = pcntl_fork();
//        if ($pid != 0) {
//            return;
//        }
        $pid = pcntl_fork();
        if ($pid > 0) {
            foreach ($this->process as $_pid) {
                (new Signal($_pid))->kill();
                //exec("kill -9 ".$_pid);
            }
//            $this->process = [];
//            $this->process[] = $pid;


            //必须等待子进程全部退出 否则子进程全部变成僵尸进程
            $start_wait = time();
            while (1) {
                $__pid = pcntl_wait($status, WNOHANG);
                if ($__pid > 0) {
                    echo $__pid, "父进程等待子进程退出\r\n";
                    foreach ($this->process as $k => $v) {
                        if ($v == $__pid) {
                            unset($this->process[$k]);
                        }
                    }
                }
                if (count($this->process) <= 0 || !$this->process) {
                    break;
                }

                if ((time() - $start_wait) > 5) {
                    echo "error : websocket等待子进程退出超时\r\n";
                }
            }

            $this->process = [];
            $this->process[] = $pid;
            echo "广播子进程";
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
                    echo $current_process_id,"广播进程收到终止信息号\r\n";
                    // exec("kill -9 ".$current_process_id);
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
                            $w->send($content);
                        }
                        unlink($item);

                        if ($run_count%$cc == 0) {
                            if ($signal->checkStopSignal()) {
                                echo $current_process_id,"广播进程收到终止信息号\r\n";
                                // exec("kill -9 ".$current_process_id);
                                exit;
                            }
                            $run_count = 0;
                        }
                        $run_count++;
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
    private function broadcast($tcp)
    {
//        $pid = pcntl_fork();
//        if ($pid != 0) {
//            return;
//        }
        $pid = pcntl_fork();
        if ($pid > 0) {
            foreach ($this->process as $_pid) {
                (new Signal($_pid))->kill();
                //exec("kill -9 ".$_pid);
            }
//            $this->process = [];
//            $this->process[] = $pid;


            //必须等待子进程全部退出 否则子进程全部变成僵尸进程
            $start_wait = time();
            while (1) {
                $__pid = pcntl_wait($status, WNOHANG);
                if ($__pid > 0) {
                    echo $__pid, "父进程等待子进程退出\r\n";
                    foreach ($this->process as $k => $v) {
                        if ($v == $__pid) {
                            unset($this->process[$k]);
                        }
                    }
                }
                if (count($this->process) <= 0 || !$this->process) {
                    break;
                }

                if ((time() - $start_wait) > 5) {
                    echo "error : websocket等待子进程退出超时\r\n";
                }
            }

            $this->process = [];
            $this->process[] = $pid;
            echo "广播子进程";
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
                    echo $current_process_id,"广播进程收到终止信息号\r\n";
                   // exec("kill -9 ".$current_process_id);
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

                        if ($run_count%$cc == 0) {
                            if ($signal->checkStopSignal()) {
                                echo $current_process_id,"广播进程收到终止信息号\r\n";
                                // exec("kill -9 ".$current_process_id);
                                exit;
                            }
                            $run_count = 0;
                        }
                        $run_count++;
                    }
                }
            }

            $run_count++;
            usleep(self::USLEEP);
        }
    }

    public function start3()
    {
        $process_id = pcntl_fork();

        if ($process_id < 0) {
            echo "fork a process fail\r\n";
            exit;
        }

        if ($process_id > 0) {
            return $process_id;
        }

        // Create a Websocket server
        $ws_worker = new \Workerman\Worker("websocket://0.0.0.0:9998");

// 4 processes
        $ws_worker->count = $this->workers;

// Emitted when new connection come
        $ws_worker->onConnect = function($connection)
        {
            $this->clients[] = $connection;
            echo "New connection\n";
            $this->broadcast();
        };

// Emitted when data received
        $ws_worker->onMessage = function($connection, $data)
        {
            // Send hello $data
            $connection->send('hello ' . $data);
        };

// Emitted when connection closed
        $ws_worker->onClose = function($connection)
        {
            $key = array_search($connection, $this->clients);
            unset($this->clients[$key]);
            $this->broadcast();
            echo "Connection closed\n";
        };

        $ws_worker->onError = function ($connection){
            $key = array_search($connection, $this->clients);
            unset($this->clients[$key]);
            $this->broadcast();
        };

        // Run worker
        \Workerman\Worker::runAll();

        return 0;
    }
    public function start($daemon = false)
    {
        $process_id = pcntl_fork();

        if ($process_id < 0) {
            echo "fork a process fail\r\n";
            exit;
        }

        if ($process_id > 0) {
            return $process_id;
        }

        if ($daemon) {
            reset_std();
        }
        //pcntl_signal(SIGCLD, SIG_IGN);

        $tcp     = new \Wing\Net\WebSocket();
        //$clients = $this->clients;

        //$is_start = false;
//        pcntl_signal(SIGALRM, function($signal) use($clients){
//            echo "时钟信号\r\n";
//            var_dump($clients);
//            pcntl_alarm(1);
//        }, true);

        $tcp->on(\Wing\Net\Tcp::ON_CONNECT, function($client, $buffer) use($tcp) {
            echo "websocket新的连接\r\n";
            var_dump(func_get_args());
            $this->clients[intval($client)] = [$buffer, $client];
            $this->broadcast($tcp);
            //$this->writeNum($clients, $tcp);
//            if (!$is_start)pcntl_alarm(1);
//            $is_start = true;
        });

        $tcp->on(\Wing\Net\Tcp::ON_RECEIVE, function($client, $buffer, $recv_msg) use($tcp){

            var_dump(func_get_args());

            if (0 === strpos($recv_msg, 'GET')) {
                echo "收到握手消息：",($recv_msg),"\r\n\r\n";
                //握手消息
                $res = $tcp->handshake($buffer, $recv_msg, $client);//, $recv_msg), $client );
                var_dump($res);
                return;
            }

           // echo "收到的消息：",\Wing\Net\WebSocket::decode($recv_msg),"\r\n\r\n";
            //一般的消息响应
            $res = $tcp->send($buffer, "hello", $client);
            var_dump($res);

        });

        $tcp->on(Tcp::ON_CLOSE, function($client, $buffer) use($tcp){
            echo "连接关闭\r\n";
            unset($this->clients[intval($client)]);
            $this->broadcast($tcp);
            //$this->writeNum($clients, $tcp);
        });

        $tcp->on(Tcp::ON_ERROR,function($client, $buffer, $error) use($tcp){
            echo "连接关闭发生错误\r\n";
            unset($this->clients[intval($client)]);
            $this->broadcast($tcp);
        });
        //pcntl_alarm(1);
        $tcp->start();
        return 0;
    }
}