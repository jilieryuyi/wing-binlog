<<<<<<< HEAD
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
    private $workers = 1;
    public function __construct($workers)
    {
        $this->workers = $workers;
        for ($i = 0; $i < $this->workers; $i++) {
            $cache = HOME . "/cache/tcp/".$i;
            (new WDir($cache))->mkdir();
        }
    }



    public function onchange($database_name, $table_name, $event)
    {
        for ($i = 0; $i < $this->workers; $i++) {

            $cache = HOME . "/cache/tcp/".$i;
            $odir = new WDir($cache);
            $odir->mkdir();
            unset($odir);
            $str1 = md5(rand(0, 999999));
            $str2 = md5(rand(0, 999999));
            $str3 = md5(rand(0, 999999));


            $cache_file = $cache . "/__" . time() .
                substr($str1, rand(0, strlen($str1) - 16), 8) .
                substr($str2, rand(0, strlen($str2) - 16), 8) .
                substr($str3, rand(0, strlen($str3) - 16), 8);

            file_put_contents($cache_file, json_encode([
                "database" => $database_name,
                "table" => $table_name,
                "event" => $event
            ]));
        }
    }
=======
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
        log("tcp client总发送次数=》", $this->send_times);
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
                log("tcp client总发送次数=》", $this->send_times);
            }
            log("tcp client总发送失败次数=》", $this->failure_times);
        }catch (\Exception $e) {
            var_dump($e->getMessage());
            $this->client = null;
        }
    }

    private function tryCreateClient() {
        try {
            $this->client = stream_socket_client("tcp://" . $this->host . ":" . $this->port, $errno, $errstr, 30);
            if (!$this->client) {
                log("stream_socket_client错误：$errstr ($errno)");
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
>>>>>>> 6ee3cbd6544d951ff92c5114316e3e698587ea1a
}