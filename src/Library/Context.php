<?php namespace Wing\Binlog\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:26
 * @property PDO $pdo 连接配置文件第一个数据库的pdo
 * @property \Redis $redis
 */
class Context{

    public $redis;
    public $pdo;

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

        $configs    = require __DIR__."/../../config/db.php";
        $this->pdo  = new \Wing\Binlog\Library\PDO(
            $configs["user"],
            $configs["password"],
            $configs["host"],
            $configs["db_name"]
        );

    }
}