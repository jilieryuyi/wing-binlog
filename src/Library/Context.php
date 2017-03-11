<?php namespace Seals\Library;
use Psr\Log\LoggerInterface;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:26
 *
 * 上下文支持
 *
 * @property PDO $activity_pdo
 * @property \Redis $redis
 * @property RedisInterface $redis_local
 * @property LoggerInterface $logger
 */
class Context{

    /**
     * @var RedisInterface
     */
    public $redis;

    /**
     * @var RedisInterface
     */
    public $redis_local;

    /**
     * @var PDO
     */
    public $activity_pdo;

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var string
     */
    public $log_dir;
    public $binlog_cache_dir  = __APP_DIR__."/cache";
    public $mysqlbinlog_bin   = "mysqlbinlog";


    private $app_config;
    private $db_config;
    public $logger;
    public $memory_limit = "10240M";
    /**
     * 单例
     *
     * @return self
     */
    public static function instance(){
        if (!self::$instance)
            self::$instance = new self();
        return self::$instance;
    }

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * 重置所有的资源，多进程编程支持
     */
    public function reset()
    {

        $this->redis        = null;
        $this->redis_local  = null;
        $this->activity_pdo = null;

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

        $configs = $this->db_config = require __DIR__."/../../config/db.php";
        $this->activity_pdo  = new \Seals\Library\PDO(
            $configs["user"],
            $configs["password"],
            $configs["host"],
            $configs["db_name"],
            $configs["port"]
       );

        $this->app_config = include __DIR__."/../../config/app.php";

        $this->log_dir = $this->app_config["log_dir"];

        if (!class_exists($this->app_config["logger"])) {
            exit($this->app_config["logger"]." class not found");
        }

        $this->logger  = new $this->app_config["logger"]($this->log_dir, $this->app_config["log_levels"]);

        if (isset($this->app_config["binlog_cache_dir"]) && $this->app_config["binlog_cache_dir"])
            $this->binlog_cache_dir  = $this->app_config["binlog_cache_dir"];

        if (isset($this->app_config["mysqlbinlog_bin"]) && $this->app_config["mysqlbinlog_bin"])
            $this->mysqlbinlog_bin   = $this->app_config["mysqlbinlog_bin"];

        if (isset($this->app_config["memory_limit"]) && $this->app_config["memory_limit"])
            $this->memory_limit = $this->app_config["memory_limit"];
    }

    public function getAppConfig($key)
    {
        return $this->app_config[$key];
    }

    public function getDbConfig($key)
    {
        return $this->db_config[$key];
    }
}