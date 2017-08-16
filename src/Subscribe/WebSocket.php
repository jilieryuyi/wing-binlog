<?php namespace Wing\Subscribe;
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

        $this->startWebsocketService($host, $port, $daemon, $workers);
        sleep(1);
        $this->tryConnect();
    }


    private function tryConnect()
    {
        $this->client = null;
        try {
            $this->client = new \Wing\Net\WsClient($this->host, $this->port, '/');
        } catch (\Exception $e){
            var_dump($e->getMessage());
			$this->client = null;
        }
    }


    private function send($msg)
    {
        $msg .= "\r\n\r\n\r\n";
        try {
            if (!$this->client->send($msg)) {
                $this->client = null;
                $this->tryConnect();
                $this->client->send($msg);
            }
        } catch(\Exception $e){
            var_dump($e->getMessage());
        }
    }

    private function startWebsocketService($host, $port, $deamon, $workers)
    {
		if (is_env(WINDOWS)) {
			$command = HOME."/services/websocket.exe ".$port;
			wing_debug($command);
//			$handle  = popen($command." >>".HOME."/logs/websocket.log&","r");
//			if ($handle) {
//				pclose($handle);
//			}
			return;
		}
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