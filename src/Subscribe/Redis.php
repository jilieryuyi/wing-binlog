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
        $host     = $config["host"];//     => "127.0.0.1",
        $port     = $config["port"];//     => 6397,
        $password = $config["password"];// => null,                          //无密码时必须为null
        $queue    = $config["queue"];//    => "----wing-mysql-events-queue----" //默认的redis队列名称，队列使用rpush从尾部进入队列

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