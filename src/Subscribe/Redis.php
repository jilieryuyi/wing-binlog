<<<<<<< HEAD
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
	public function __construct()
	{
        $config = load_config("app");
        $this->redis = new \Wing\Library\Redis(
            $config["redis"]["host"],
            $config["redis"]["port"],
            $config["redis"]["password"]
        );
        $this->queue = $config["redis"]["queue"];
	}



	public function onchange($database_name, $table_name, $event)
	{
        $this->redis->rpush($this->queue, json_encode(
            [
                "database" => $database_name,
                "table" => $table_name,
                "event" => $event
            ]
        ));
	}
=======
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
>>>>>>> 6ee3cbd6544d951ff92c5114316e3e698587ea1a
}