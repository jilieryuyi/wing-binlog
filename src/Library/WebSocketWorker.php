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
class WebSocketWorker extends BaseWorker
{
    private $clients = [];
    private $processes = [];

    public function __construct()
    {
        $dir = HOME."/cache/websocket";
        (new WDir($dir))->mkdir();
    }

    /**
     * signal handler
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {
        $server_id = file_get_contents(HOME."/websocket.pid");

        switch ($signal) {
            //stop all
            case SIGINT:
                if ($server_id == get_current_processid()) {
                    foreach ($this->processes as $id => $pid) {
                        posix_kill($pid, SIGINT);
                    }

                    $start = time();
                    $max = 1;
                    while (1) {
                        $pid = pcntl_wait($status, WNOHANG);//WUNTRACED);
                        if ($pid > 0) {
                            $id = array_search($pid, $this->processes);
                            unset($this->processes[$id]);
                        }

                        if (!$this->processes || count($this->processes) <= 0) {
                            break;
                        }

                        if (time() - $start > $max && $this->processes) {
                            foreach ($this->processes as $id => $pid) {
                                posix_kill($pid, SIGINT);
                            }
                            $max++;
                        }

                        if ((time() - $start) >= 5) {
                            echo "退出进程超时\r\n";
                            break;
                        }


                    }
                    echo "父进程";
                }
                echo get_current_processid(),"----收到退出信号退出\r\n";
                exit(0);
                break;
            //restart
            case SIGUSR1:
//                if ($server_id == get_current_processid()) {
//                    foreach ($this->processeses as $id => $pid) {
//                        posix_kill($pid,SIGINT);
//                    }
//                }

//                $cache = new File(__APP_DIR__);
//                list($deamon, $workers, $debug, $clear) = $cache->get(self::RUNTIME);
//
//                $command = "php ".__APP_DIR__."/seals server:start --n ".$workers;
//                if ($deamon)
//                    $command .= ' --d';
//                if ($debug)
//                    $command .= ' --debug';
//                if ($clear)
//                    $command .= ' --clear';
//
//                //$shell = "#!/bin/bash\r\n".$command;
//                //file_put_contents(__APP_DIR__."/restart.sh", $shell);
//                $handle = popen("/bin/sh -c \"".$command."\" >>".Context::instance()->log_dir."/server_restart.log&","r");
//
//                if ($handle) {
//                    pclose($handle);
//                }

                exit(0);
                break;
        }
    }

    /**
     * @param WebSocket $tcp
     */
    private function broadcast($tcp)
    {
        if (count($this->clients) < 0) {
            return;
        }
        $_dir = HOME."/cache/running";
        if (!is_dir($_dir)) {
            $dir = new WDir($_dir);
            $dir->mkdir();
            unset($dir);
        }

        $running_file = $_dir."/websocket_broadcast";
        $exit_file    = $_dir."/websocket_exit_signal";
        $is_running   = false;

        if (file_exists($running_file)) {
            if ((time() - file_get_contents($running_file))<=1) {
                $is_running = true;
            }
        }

        if ($is_running) {
            file_put_contents($exit_file ,  1);
        }

        while ($is_running) {
            if ((time() - file_get_contents($running_file))<=1) {
                $is_running = true;
            } else {
                $is_running = false;
            }
            if (count($this->processes) > 1) {
                file_put_contents($exit_file ,  1);
            }
            usleep(self::USLEEP*2);
        }

        $new_processid = pcntl_fork();
        if ($new_processid > 0) {
            echo $new_processid,"创建了新的广播进程\r\n";

            //必须等待子进程全部退出 否则子进程全部变成僵尸进程
            if (count($this->processes) > 0) {
                echo "=======>等待子进程退出<=======\r\n";

                    $start = time();
                    while (1) {
                        $pid = pcntl_wait($status, WNOHANG);//WUNTRACED);
                        if ($pid > 0) {
                            $id = array_search($pid, $this->processes);
                            unset($this->processes[$id]);
                        }

                        if (!$this->processes || count($this->processes) <= 0) {
                            break;
                        }

                        if ((time() - $start) >= 5) {
                            echo "退出进程超时\r\n";
                            break;
                        }
                    }
                    echo "父进程";

            }

            echo "这个打印必须要是空的才正常";
            var_dump($this->processes);

            $this->processes[] = $new_processid;
            $this->processes   = array_values($this->processes);
            echo "广播子进程";
            var_dump($this->processes);

            return;
        }

        set_process_title("wing php >> websocket broadcast process");
        $current_process_id = get_current_processid();

        $run_count = 0;
        while (1) {
            file_put_contents($running_file, time());
            if (file_get_contents($exit_file) == 1) {

                echo $current_process_id,"****************************广播进程收到终止信息号1\r\n";
                file_put_contents($exit_file, 0);
                exit;
            }
            //广播消息
            $path[] = HOME . "/cache/websocket/*";
            while (count($path) != 0) {
                $v = array_shift($path);
                foreach (glob($v) as $item) {
                    if (is_file($item)) {
                        file_put_contents($running_file, time());
                        $content = file_get_contents($item);
                        foreach ($this->clients as $w) {
                            echo "发送消息：", $content, "\r\n";
                            $tcp->send($w[0], $content, $w[1]);
                        }
                        unlink($item);

                        if (file_get_contents($exit_file) == 1) {

                            echo $current_process_id,"****************************广播进程收到终止信息号2\r\n";
                            file_put_contents($exit_file, 0);
                            exit;
                        }
                            $run_count = 0;
                        $run_count++;
                    }
                }
            }

            $run_count++;
            usleep(self::USLEEP);
        }
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

//        pcntl_signal(SIGINT,  [$this, 'signalHandler'], false);
//        pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
//        pcntl_signal(SIGPIPE, SIG_IGN, false);
//        file_put_contents(HOME."/websocket.pid", get_current_processid());
//

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
            //var_dump(func_get_args());
            $this->clients[intval($client)] = [$buffer, $client];
            $this->broadcast($tcp);
            //$this->writeNum($clients, $tcp);
//            if (!$is_start)pcntl_alarm(1);
//            $is_start = true;
        });

        $tcp->on(\Wing\Net\Tcp::ON_RECEIVE, function($client, $buffer, $recv_msg) use($tcp){

          //  var_dump(func_get_args());

            if (0 === strpos($recv_msg, 'GET')) {
               // echo "收到握手消息：",($recv_msg),"\r\n\r\n";
                //握手消息
                $res = $tcp->handshake($buffer, $recv_msg, $client);//, $recv_msg), $client );
               // var_dump($res);
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