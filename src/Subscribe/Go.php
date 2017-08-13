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
    private $workers = 1;
    public function __construct($config)
    {
        $host    = $config["host"];
        $port    = $config["port"];
        $daemon  = $config["daemon"];
        $workers = $config["workers"];
        $this->workers = $workers;
        for ($i = 0; $i < $this->workers; $i++) {
            $cache = HOME . "/cache/tcp/".$i;
            (new WDir($cache))->mkdir();
        }
        $this->startTcpService($host, $port, $daemon, $workers);
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

    public function onchange($event)
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

            file_put_contents($cache_file, json_encode($event));
        }
    }
}