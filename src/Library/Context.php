<?php namespace Seals\Library;
use Psr\Log\LoggerInterface;
use Seals\Cache\File;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:26
 *
 * context support
 *
 * @property array $redis_config
 * @property PDO $activity_pdo
 * @property RedisInterface $redis_local
 * @property LoggerInterface $logger
 * @property RedisInterface $redis_zookeeper
 */
class Context{

    /**
     * @var RedisInterface
     */
    public $redis_local        = null;
    public $redis_local_config = [];
    public $redis_zookeeper    = null;

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


    private $app_config      = [];
    public $db_config        = [];
    //事件队列redis
    public $redis_config     = [];
    public $rabbitmq_config  = [];
    public $zookeeper_config = [];
    public $notify_config    = [];

    public $logger;
    public $memory_limit     = "10240M";
    public $session_id;

    protected $static_instances = [];


    public $master_listen = "0.0.0.0";
    public $master_port = 9998;
    public $master_auto_update = true;
    public $master_logs_limit = 100000;
    public $lang = "zh";

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

    public function get($key)
    {
        if (!$this->static_instances[$key])
            return null;
        return $this->static_instances[$key];
    }

    public function set($key, $value){
        $this->static_instances[$key] = $value;
        return $this;
    }

    /**
     * __construct, init configs and local redis
     */
    public function __construct()
    {
        $str1 = md5(rand(0,999999));
        $str2 = md5(rand(0,999999));
        $str3 = md5(rand(0,999999));

        $cache   = new File(__APP_DIR__);
        $session = $cache->get("session");
        if (!$session) {
            $this->session_id = time() . "-" .
                substr($str1, rand(0, strlen($str1) - 16), 16) .
                substr($str2, rand(0, strlen($str2) - 16), 16) .
                substr($str3, rand(0, strlen($str3) - 16), 16);
            $cache->set("session", $this->session_id);
        } else {
            $this->session_id = $session;
        }
        unset($cache, $session, $str1, $str2, $str3);

        $this->init();
        $this->initRedisLocal();

        $master_config = require __DIR__."/../../config/master.php";

        if (isset($master_config["listen"]))
            $this->master_listen = $master_config["listen"];
        if (isset($master_config["port"]) && intval($master_config["port"]) > 0)
            $this->master_port = $master_config["port"];

        if (isset($master_config["auto_update"]))
            $this->master_auto_update = !!$master_config["auto_update"];

        if (isset($master_config["logs_limit"]) && $master_config["logs_limit"] > 0)
            $this->master_logs_limit = $master_config["logs_limit"];

        if (isset($master_config["lang"]) && $master_config["lang"] > 0)
            $this->lang = $master_config["lang"];

        if (!in_array($this->lang, \Seals\Library\Lang::$ltypes))
            $this->lang = "zh";

    }

    /**
     * init local redis source
     *
     * @return self
     */
    public function initRedisLocal()
    {
        $this->redis_local        = null;
        $this->redis_local_config = require __DIR__."/../../config/redis_local.php";

        $this->redis_local  = new Redis(
            $this->redis_local_config["host"],
            $this->redis_local_config["port"],
            $this->redis_local_config["password"]
        );
        return $this;
    }

    /**
     * init context pdo source
     *
     * @return self
     */
    public function initPdo()
    {
        $this->activity_pdo = null;
        $configs            = $this->db_config
                            = require __DIR__."/../../config/db.php";

        if (!isset($configs["port"]) || !$configs["port"]) {
            $configs["port"] = 3306;
        }

        $this->activity_pdo  = new \Seals\Library\PDO(
            $configs["user"],
            $configs["password"],
            $configs["host"],
            $configs["db_name"],
            $configs["port"]
        );
        return $this;
    }

    /**
     * config init
     */
    public function init()
    {
        //rabbitmq 通知配置 可选
        if (file_exists(__DIR__."/../../config/rabbitmq.php"))
            $this->rabbitmq_config = require __DIR__."/../../config/rabbitmq.php";

        //事件通知redis 可选
        if (file_exists(__DIR__."/../../config/redis.php"))
            $this->redis_config    = require __DIR__."/../../config/redis.php";

        $this->app_config   = include __DIR__."/../../config/app.php";

        $this->log_dir      = $this->app_config["log_dir"];

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
            trigger_error($this->app_config["logger"]." class not found");
            $this->app_config["logger"] = \Seals\Logger\Local::class;
        }

        $this->logger  = new $this->app_config["logger"]($this->log_dir, $this->app_config["log_levels"]);

        if (isset($this->app_config["binlog_cache_dir"]) && $this->app_config["binlog_cache_dir"])
            $this->binlog_cache_dir  = $this->app_config["binlog_cache_dir"];

        if (isset($this->app_config["mysqlbinlog_bin"]) && $this->app_config["mysqlbinlog_bin"])
            $this->mysqlbinlog_bin   = $this->app_config["mysqlbinlog_bin"];

        if (isset($this->app_config["memory_limit"]) && $this->app_config["memory_limit"])
            $this->memory_limit = $this->app_config["memory_limit"];

        $this->zookeeper_config = null;
        if (file_exists(__DIR__."/../../config/zookeeper.php"))
            $this->zookeeper_config = require __DIR__."/../../config/zookeeper.php";

        if (file_exists(__DIR__."/../../config/notify.php")) {
            $this->notify_config = require __DIR__."/../../config/notify.php";
        }

        return $this;
    }

    public function zookeeperInit()
    {
        if (!isset($this->zookeeper_config["host"]) || !isset($this->zookeeper_config["port"]))
            return $this;

        if (!isset($this->zookeeper_config["password"]))
            $this->zookeeper_config["password"] = null;

        $this->redis_zookeeper  = null;
        $this->redis_zookeeper  = new Redis(
            $this->zookeeper_config["host"],
            $this->zookeeper_config["port"],
            $this->zookeeper_config["password"]
        );
        return $this;
    }

    /**
     * get app config
     *
     * @param string $key
     * @return mixed
     */
    public function getAppConfig($key)
    {
        if (!isset($this->app_config[$key]))
            return null;
        return $this->app_config[$key];
    }

    /**
     * get database config
     *
     * @param string $key
     * @return mixed
     */
    public function getDbConfig($key)
    {
        if (!isset($this->db_config[$key]))
            return null;
        return $this->db_config[$key];
    }


}