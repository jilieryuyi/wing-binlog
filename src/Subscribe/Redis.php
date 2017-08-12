<?php namespace Wing\Subscribe;
use Wing\Library\ISubscribe;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:58
 *
 * @property \Redis $redis
 */
class Redis implements ISubscribe
{
    private $redis;
    private $queue;
	public function __construct($host, $port, $password, $queue)
	{
        //$config = load_config("app");
        $this->redis = new \Wing\Library\Redis(
            $host,//$config["redis"]["host"],
            $port,//$config["redis"]["port"],
            $password//$config["redis"]["password"]
        );
        $this->queue = $queue;//$config["redis"]["queue"];
	}



	public function onchange($event)
	{
        $this->redis->rpush($this->queue, json_encode($event));
	}
}