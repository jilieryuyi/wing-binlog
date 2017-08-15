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
class WebSocket implements ISubscribe
{
    private $workers = 1;
    public function __construct($workers)
    {
        $this->workers = $workers;
        for ($i = 0; $i < $this->workers; $i++) {
            $cache = HOME."/cache/websocket/".$i;
            (new WDir($cache))->mkdir();
        }
    }


    public function onchange($database_name, $table_name, $event)
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

            file_put_contents($cache_file, json_encode([
                "database" => $database_name,
                "table" => $table_name,
                "event" => $event
            ]));
        }
        //    file_put_contents($cache_file, json_encode([$database_name, $table_name, $event]));
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
class WebSocket implements ISubscribe
{
    private $workers = 1;
    private $client = null;
    private $host;
    private $port;
    public function __construct($config)
    {
        $host    = $config["host"];
        $port    = $config["port"];
        $daemon  = $config["daemon"];
        $workers = $config["workers"];

        $this->workers = $workers;
        $this->host    = $host;
        $this->port    = $port;
//        for ($i = 0; $i < $this->workers; $i++) {
//            $cache = HOME."/cache/websocket/".$i;
//            (new WDir($cache))->mkdir();
//        }
        $this->startWebsocketService($host, $port, $daemon, $workers);
        sleep(1);
        $this->tryConnect();
    }


    private function tryConnect($msg = null)
    {
        $this->client = null;
        try {
            $this->client = new \Wing\Net\WsClient($this->host, $this->port, '/');
		    //$res = $this->client->connect();

//		    if ($res) {
//		        echo "连接成功\r\n";
//            } else {
//		        echo "连接失败\r\n";
//            }

//		$payload = json_encode(array(
//            'code' => 'xxx',
//            'id' => '1'
//        ));
//		$rs = $client->sendData($payload);
//
        } catch (\Exception $e){
            var_dump($e->getMessage());
        }
    }


    private function send($msg)
    {
        $msg .= "\r\n\r\n\r\n";
        try {
            if (!$this->client->send($msg)) {
                $this->client = null;
                $this->tryConnect($msg);
                $this->client->send($msg);
            }
        } catch(\Exception $e){
            var_dump($e->getMessage());
        }
    }

    private function startWebsocketService($host, $port, $deamon, $workers)
    {
        $command = "php ".HOME."/services/websocket start --host=".$host." --port=".$port." --workers=".$workers;
        if ($deamon) {
            $command .= " -d";
        }
        $handle  = popen("/bin/sh -c \"".$command."\" >>".HOME."/logs/websocket.log&","r");
        if ($handle) {
            pclose($handle);
        }
    }


    public function onchange($event)
    {
        $this->send(json_encode($event));
    }
>>>>>>> 6ee3cbd6544d951ff92c5114316e3e698587ea1a
}