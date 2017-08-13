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
    public function __construct($config)
    {
        $this->host    = $config["host"];
        $this->port    = $config["port"];
        $this->client  = null;
    }

    private function tryCreateClient() {
        try {
            $this->client = stream_socket_client("tcp://" . $this->host . ":" . $this->port, $errno, $errstr, 30);
            if (!$this->client) {
                echo "$errstr ($errno)<br />\n";
            } else {
//                fwrite($fp, "GET / HTTP/1.0\r\nHost: www.example.com\r\nAccept: */*\r\n\r\n");
//                while (!feof($fp)) {
//                    echo fgets($fp, 1024);
//                }
//                fclose($fp);
            }
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->client = null;
        }
    }


    public function onchange($event)
    {
        if (!$this->client) {
            $this->tryCreateClient();
        }
        try {
            if (false === fwrite($this->client, json_encode($event) . "\r\n\r\n\r\n")) {
                $this->client = null;
                $this->tryCreateClient();
                fwrite($this->client, json_encode($event) . "\r\n\r\n\r\n");
            }
        }catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->client = null;
        }
    }
}