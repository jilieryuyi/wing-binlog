<?php namespace Wing\Subscribe;
use Wing\FileSystem\WDir;
use Wing\Library\ISubscribe;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:58
 */
class Go implements ISubscribe
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
    }


    private function send($msg)
    {
        $this->send_times++;
        wing_log("tcp client总发送次数：", $this->send_times);

        try {

            if (!$this->client) {
                $this->tryCreateClient();
            }
            if (!fwrite($this->client, $msg . "\r\n\r\n\r\n")) {
                $this->client = null;
                $this->failure_times++;

                $this->tryCreateClient();
                fwrite($this->client, $msg . "\r\n\r\n\r\n");
                $this->send_times++;
                wing_log("tcp client总发送次数：", $this->send_times);
            }
            wing_log("tcp client总发送失败次数：", $this->failure_times);
        }catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->client = null;
        }
    }

    private function tryCreateClient() {
        try {
            $this->client = stream_socket_client("tcp://" . $this->host . ":" . $this->port, $errno, $errstr, 30);
            if (!$this->client) {
                wing_log("发生错误：$errstr ($errno)");
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