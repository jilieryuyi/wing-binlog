<?php namespace Wing\Subscribe;
use Wing\FileSystem\WDir;
use Wing\Library\ISubscribe;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:58
 */
class WebSocket implements ISubscribe
{
    private $workers = 1;
    public function __construct($config)
    {
        $host = $config["host"];
        $port = $config["port"];
        $daemon = $config["daemon"];
        $workers = $config["workers"];

        $this->workers = $workers;
        for ($i = 0; $i < $this->workers; $i++) {
            $cache = HOME."/cache/websocket/".$i;
            (new WDir($cache))->mkdir();
        }
        $this->startWebsocketService($host, $port, $daemon, $workers);
    }

    private function startWebsocketService($host, $port, $deamon, $workers)
    {
//        $config = load_config("app");
//        $host = isset($config["websocket"]["host"])?$config["websocket"]["host"]:"0.0.0.0";
//        $port = isset($config["websocket"]["port"])?$config["websocket"]["port"]:9998;

        $command = "php ".HOME."/services/websocket start --host=".$host." --port=".$port." --workers=".$workers;
        if ($deamon) {
            $command .= " -d";
        }
        echo $command,"\r\n";
        $handle  = popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/websocket.log&","r");

        if ($handle) {
            pclose($handle);
        }
    }


    public function onchange($event)
    {

        for ($i = 0; $i < $this->workers; $i++) {
            $cache = HOME . "/cache/websocket/".$i;

            $str1 = md5(rand(0, 999999));
            $str2 = md5(rand(0, 999999));
            $str3 = md5(rand(0, 999999));


            $cache_file = $cache . "/__" . time() .
                substr($str1, rand(0, strlen($str1) - 16), 8) .
                substr($str2, rand(0, strlen($str2) - 16), 8) .
                substr($str3, rand(0, strlen($str3) - 16), 8);

            file_put_contents($cache_file, json_encode($event));
        }
        //    file_put_contents($cache_file, json_encode([$database_name, $table_name, $event]));
    }
}