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
 * @property \Redis $redis_local
 * @property LoggerInterface $logger
 * @property Notify $notify
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

    private $app_config;
    private $db_config;
    public $logger;
    public $notify;

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

        $this->app_config = include __APP_DIR__."/config/app.php";

        $this->log_dir = $this->app_config["log_dir"];

        if (!class_exists($this->app_config["logger"])) {
            exit($this->app_config["logger"]." class not found");
        }

        $this->logger  = new $this->app_config["logger"]($this->log_dir, $this->app_config["log_levels"]);

        $handlers_config = include __APP_DIR__."/config/notify.php";
        $handler_class   = $handlers_config["handler"];

        if (!class_exists($handler_class)) {
            exit($handler_class." class not found");
        }

        $len     = count($handlers_config["params"]);
        $handler = null;

        switch ($len) {
            case 0:
                $handler = new $handler_class;
                break;
            case 1:
                $handler = new $handler_class($handlers_config["params"][0]);
                break;
            case 2:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1]
                );
                break;
            case 3:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2]
                );
                break;
            case 4:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3]
                );
                break;
            case 5:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4]
                );
                break;
            case 6:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4],
                    $handlers_config["params"][5]
                );
                break;
            case 7:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4],
                    $handlers_config["params"][5],
                    $handlers_config["params"][6]
                );
                break;
            case 8:
                $handler = new $handler_class(
                    $handlers_config["params"][0],
                    $handlers_config["params"][1],
                    $handlers_config["params"][2],
                    $handlers_config["params"][3],
                    $handlers_config["params"][4],
                    $handlers_config["params"][5],
                    $handlers_config["params"][6],
                    $handlers_config["params"][7]
                );
                break;
            default:
                $handler = new $handler_class;
                break;
        }

        $this->notify = $handler;

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