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
}