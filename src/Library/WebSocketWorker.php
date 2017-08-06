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
    private $clients   = [];
    private $processes = [];

    public function __construct()
    {
        $dir = HOME."/cache/websocket";
        $obj_dir = new WDir($dir);
        $obj_dir->mkdir();
        unset($obj_dir, $dir);
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

        while ($is_running) {
            if ((time() - file_get_contents($running_file))<=1) {
                $is_running = true;
            } else {
                $is_running = false;
            }
            file_put_contents($exit_file ,  1);
            usleep(self::USLEEP*2);
        }

        file_put_contents($exit_file ,  0);

        $new_processid = pcntl_fork();
        if ($new_processid > 0) {

            //必须等待子进程全部退出 否则子进程全部变成僵尸进程
            if (count($this->processes) > 0) {

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
            }

            $this->processes[] = $new_processid;
            $this->processes   = array_values($this->processes);

            return;
        }

        set_process_title("wing php >> websocket broadcast process");

        while (1) {
            file_put_contents($running_file, time());

            if (file_exists($exit_file) && file_get_contents($exit_file) == 1) {
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
                            $tcp->send($w[0], $content, $w[1]);
                        }
                        unlink($item);

                        if (file_exists($exit_file) && file_get_contents($exit_file) == 1) {
                            file_put_contents($exit_file, 0);
                            exit;
                        }
                    }
                }
            }

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
        set_process_title("wing php >> websocket socket service process");
        $tcp = new \Wing\Net\WebSocket();

        $tcp->on(\Wing\Net\Tcp::ON_CONNECT, function($client, $buffer) use($tcp) {
            $this->clients[intval($client)] = [$buffer, $client];
            $this->broadcast($tcp);
        });

        $tcp->on(\Wing\Net\Tcp::ON_RECEIVE, function($client, $buffer, $recv_msg) use($tcp){
            if (0 === strpos($recv_msg, 'GET')) {
                $tcp->handshake($buffer, $recv_msg, $client);//, $recv_msg), $client );
                return;
            }
            //$res = $tcp->send($buffer, "hello", $client);
            //var_dump($res);
        });

        $tcp->on(Tcp::ON_CLOSE, function($client, $buffer) use($tcp){
            unset($this->clients[intval($client)]);
            $this->broadcast($tcp);
        });

        $tcp->on(Tcp::ON_ERROR,function($client, $buffer, $error) use($tcp){
            unset($this->clients[intval($client)]);
            $this->broadcast($tcp);
        });

        $tcp->start();
        return 0;
    }
}