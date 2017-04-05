<?php namespace Seals\Logger;
use Psr\Log\LoggerInterface;
use Seals\Library\Context;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/11
 * Time: 08:59
 * 本地日志存储实现
 */
class Local implements LoggerInterface
{

    private $log_dir;
    private $levels;

    public function __construct($log_dir, array $levels)
    {
        $dir = str_replace("\\","/",$log_dir);
        $dir = rtrim($dir,"/");
        $this->log_dir = $dir;
        $dir = new WDir($this->log_dir);
        $dir->mkdir();
        unset($dir);

        $this->levels = $levels;
    }

    private function write($name, $message, array $context = null)
    {
        //如果是非定义记录的级别 不采取任何操作
        if (!in_array($name,$this->levels)) {
            echo "非定义级别\r\n";
            return;
        }
        if ( !$message && !$context) {
            echo "空日志\r\n";
            return;
        }

        $content = date("Y-m-d H:i:s") . "\r\n";

        if ($message)
            $content .= $message."\r\n";

        if ($context)
            $content .= json_encode($context, JSON_UNESCAPED_UNICODE)."\r\n";

        $content .= "\r\n";

        $file = new WFile($this->log_dir . "/" . $name . "_" . date("Ymd") . ".log");
        $file->write($content,1,1);
//        file_put_contents(
//            $this->log_dir . "/" . $name . "_" . date("Ymd") . ".log",
//            $content,
//            FILE_APPEND
//        );
        unset($file);

        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        $log_data = json_encode([
            "level"      => $name,
            "message"    => $message,
            "context"    => $context,
            "time"       => time()
        ]);

        //logs report
        Context::instance()->redis_zookeeper->rpush(
            "wing-binlog-logs-content-".Context::instance()->session_id,
            $log_data
        );
        Context::instance()->redis_zookeeper->rpush(
            "wing-binlog-logs-list",
            $log_data
        );
        //logs count
        Context::instance()->redis_zookeeper->incr("wing-binlog-logs-count");
        Context::instance()->redis_zookeeper->incr("wing-binlog-logs-count-".date("Ymd"));
    }

    /**
     * global, get all node logs list, master process use
     *
     * @param string $session_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function get($session_id, $page, $limit)
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        //logs report
        $start = ($page-1) * $limit;
        $end   = $page * $limit-1;
        $data  = Context::instance()->redis_zookeeper->lrange(
            "wing-binlog-logs-content-".$session_id,
            $start,
            $end
        );
        $res = [];
        foreach ($data as $row) {
            $res[] = json_decode($row, true);
        }
        return $res;
    }

    /**
     * global, get all logs list, master process use
     *
     * @param string $session_id
     * @param int $page
     * @param int $limit
     * @return array
     */
    public static function getAll($page, $limit)
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();

        $len = self::getAllCount();
        //logs report
        $start = $len - ($page) * $limit;
        $end   = $len - ($page-1) * $limit;
        $data  = Context::instance()->redis_zookeeper->lrange(
            "wing-binlog-logs-list",
            $start,
            $end
        );
        $res = [];
        foreach ($data as $row) {
            $res[] = json_decode($row, true);
        }
        return $res;
    }
    public static function getAllCount()
    {
        return Context::instance()->redis_zookeeper->llen("wing-binlog-logs-list");
    }


    /**
     * global, get all logs count, master process use
     *
     * @return int
     */
    public static function countAll()
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();
        //logs count
        return Context::instance()->redis_zookeeper->incr("wing-binlog-logs-count");

    }
    /**
     * global, get all logs count in a day, master process use
     *
     * @return int
     */
    public static function countDay($day)
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();
        return Context::instance()->redis_zookeeper->incr("wing-binlog-logs-count-".$day);
    }


    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->write(__FUNCTION__, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $this->write($level, $message, $context);
    }

    public static function clear()
    {
        if (!Context::instance()->redis_zookeeper)
            Context::instance()->zookeeperInit();
        $keys = Context::instance()->redis_zookeeper->keys("wing-binlog-logs-content-*");
        Context::instance()->redis_zookeeper->del($keys);
    }
}
