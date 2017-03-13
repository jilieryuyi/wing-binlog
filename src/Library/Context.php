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
 * @property array $redis_config
 * @property PDO $activity_pdo
 * @property RedisInterface $redis_local
 * @property LoggerInterface $logger
 */
class Context{

    /**
     * @var RedisInterface
     */
    //public $redis;

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


    private $app_config     = [];
    private $db_config      = [];
    public $redis_config    = [];
    public $rabbitmq_config = [];

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

        //事件通知redis 可选
        if (file_exists(__DIR__."/../../config/redis.php"))
            $this->redis_config    = require __DIR__."/../../config/redis.php";

        //rabbitmq 通知配置 可选
        if (file_exists(__DIR__."/../../config/rabbitmq.php"))
            $this->rabbitmq_config = require __DIR__."/../../config/rabbitmq.php";

        $redis_config = require __DIR__."/../../config/redis_local.php";

        $this->redis_local  = new Redis(
            $redis_config["host"],
            $redis_config["port"],
            $redis_config["password"]
       );

        $configs = $this->db_config = require __DIR__."/../../config/db.php";
        if (!isset($configs["port"]) || !$configs["port"])
            $configs["port"] = 3306;
        $this->activity_pdo  = new \Seals\Library\PDO(
            $configs["user"],
            $configs["password"],
            $configs["host"],
            $configs["db_name"],
            $configs["port"]
       );

        $this->app_config = include __DIR__."/../../config/app.php";

        $this->log_dir = $this->app_config["log_dir"];

        if (!isset($this->app_config["logger"]))
            $this->app_config["logger"] = \Seals\Logger\Local::class;

        if (!isset($this->app_config["log_levels"]) || !is_array($this->app_config["log_levels"])) {
            $this->app_config["log_levels"] = [
                \Psr\Log\LogLevel::ALERT,
                \Psr\Log\LogLevel::CRITICAL,
                \Psr\Log\LogLevel::DEBUG,
                \Psr\Log\LogLevel::EMERGENCY,
                \Psr\Log\LogLevel::ERROR,
                \Psr\Log\LogLevel::INFO,
                \Psr\Log\LogLevel::NOTICE,
                \Psr\Log\LogLevel::WARNING
            ];
        }

        if (!class_exists($this->app_config["logger"])) {
            $this->app_config["logger"] = \Seals\Logger\Local::class;
            trigger_error($this->app_config["logger"]." class not found");
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
        if (!isset($this->app_config[$key]))
            return null;
        return $this->app_config[$key];
    }

    public function getDbConfig($key)
    {
        if (!isset($this->db_config[$key]))
            return null;
        return $this->db_config[$key];
    }
}