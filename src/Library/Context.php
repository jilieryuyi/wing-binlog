<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:26
 * @property PDO $activity_pdo
 * @property \Redis $redis
 * @property \Redis $redis_local
 */
class Context{

    public $redis;
    public $redis_local;
    public $activity_pdo;

    private static $instance = null;

    public static function instance(){
        if( !self::$instance )
            self::$instance = new self();
        return self::$instance;
    }

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $redis_config = require __DIR__."/../../config/redis.php";

        $this->redis  = new Redis(
            $redis_config["host"],
            $redis_config["port"],
            $redis_config["password"]
        );

        $redis_config = require __DIR__."/../../config/redis_local.php";

        $this->redis_local  = new Redis(
            $redis_config["host"],
            $redis_config["port"],
            $redis_config["password"]
        );

        $configs = require __DIR__."/../../config/db.php";
        $this->activity_pdo  = new \Seals\Library\PDO(
            $configs["user"],
            $configs["password"],
            $configs["host"],
            $configs["db_name"]
        );
    }
}