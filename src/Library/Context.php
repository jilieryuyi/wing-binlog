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

    private $connectors = [];

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
    /**
     * 获取数据库连接
     * @param string $sb_name 数据库名称
     *
     * @return PDO
     */
    public function getPdo( $sb_name ){
        if( !isset( $this->connectors[$sb_name] ))
            return null;
        return $this->connectors[$sb_name];
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
        $db_name    = array_keys($configs)[0];
        $this->pdo  = new \Wing\Binlog\Library\PDO(
            $configs[ $db_name ]["user"],
            $configs[ $db_name ]["password"],
            $configs[ $db_name ]["host"],
            $db_name
        );

        foreach ( $configs as $db_name => $config ){
            $this->connectors[$db_name] = new \Wing\Binlog\Library\PDO(
                $config["user"],
                $config["password"],
                $config["host"],
                $db_name
            );
        }
    }
}