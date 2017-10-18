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

	public function __construct($config)
	{
        $host     = $config["host"];
        $port     = $config["port"];
        $password = $config["password"];
        $queue    = $config["queue"];

        $this->redis = new \Wing\Library\Redis(
            $host,
            $port,
            $password
        );
        $this->queue = $queue;
	}

	public function onchange($event)
	{
        $this->redis->rpush($this->queue, json_encode($event));
	}
}