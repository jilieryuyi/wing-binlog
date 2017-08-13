<?php namespace Wing\Subscribe;
use Wing\FileSystem\WDir;
use Wing\Library\ISubscribe;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:58
 */
class Tcp implements ISubscribe
{
    private $host;
    private $port;
    private $client;
    private $send_times    = 0;
    private $failure_times = 0;
    public function __construct($config)
    {
        $this->host    = $config["host"];
        $this->port    = $config["port"];
        $this->client  = null;

        $daemon  = $config["daemon"];
        $workers = $config["workers"];
        $this->startTcpService($this->host, $this->port, $daemon, $workers);
    }

    private function startTcpService($host, $port,$deamon, $workers)
    {
        $command = "php ".HOME."/services/tcp start --host=".$host." --port=".$port." --workers=".$workers;
        if ($deamon) {
            $command .= " -d";
        }
        $handle  = popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/tcp.log&","r");
        if ($handle) {
            pclose($handle);
        }
    }

    private function send($msg)
    {
        $this->send_times++;
        echo "tcp client总发送次数=》", $this->send_times, "\r\n";
        try {

            if (!$this->client) {
                $this->tryCreateClient();
            }
            if (!fwrite($this->client, $msg . "\r\n\r\n\r\n")) {
                $this->client = null;
                $this->failure_times++;
                //$this->tryCreateClient();
                fwrite($this->client, $msg . "\r\n\r\n\r\n");
                $this->send_times++;
                echo "tcp client总发送次数=》", $this->send_times, "\r\n";
            }
            echo "tcp client总发送失败次数=》", $this->failure_times, "\r\n";
        }catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->client = null;
        }
    }

    private function tryCreateClient() {
        try {
            $this->client = stream_socket_client("tcp://" . $this->host . ":" . $this->port, $errno, $errstr, 30);
            if (!$this->client) {
                echo "$errstr ($errno)<br />\n";
                $this->client = null;
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->client = null;
        }
    }


    public function onchange($event)
    {
        $this->send(json_encode($event));
    }
}