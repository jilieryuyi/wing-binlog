<?php namespace Seals\Library;
use Seals\Cache\File;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/17
 * Time: 13:04
 */
class GeneralLog
{
    protected $pdo;
    public $last_time = 0;
    protected $cache;
    protected $file_cache;
    public function __construct(DbInterface $pdo)
    {
        $this->pdo        = $pdo;
        $this->cache      = new \Seals\Cache\Redis(Context::instance()->redis_local);
        $this->file_cache = new File(__APP_DIR__."/data/table");
        $this->last_time  = $this->getLastTime();
    }

    public function open()
    {
        $sql = 'set @@global.general_log=1';
        return $this->pdo->query($sql);
    }

    public function close()
    {
        $sql = 'set @@global.general_log=0';
        return $this->pdo->query($sql);
    }
    public function getLogPath()
    {
        $key  = "select.general_log_file.table";
        $path = $this->file_cache->get($key);
        if ($path) {
            return $path;
        }

        $sql  = 'select @@general_log_file';
        $data = $this->pdo->row($sql);

        if (!isset($data["@@general_log_file"]))
            return null;

        $path = $data["@@general_log_file"];
        $this->file_cache->set($key, $path, 60);
        return $path;
    }
    public function isOpen()
    {
        $sql  = 'select @@general_log';
        $data = $this->pdo->row($sql);

        return isset($data["@@general_log"]) && $data["@@general_log"] == 1;
    }

    public function logOutput()
    {
        $sql   = 'select @@log_output';
        $row   = $this->pdo->row($sql);
        $type1 = $row["@@log_output"];
        if (strpos($row["@@log_output"],",") !== false) {
            list($type1, ) = explode(",", $row["@@log_output"]);
        }
        return strtolower($type1);
    }

    public function query($last_time = 0, $limit = 10000)
    {
        if ($last_time <= 0)
            $last_time = date("Y-m-d 00:00:00");
        $sql  = 'select * from mysql.general_log where command_type = "Query" and 
event_time > "'.$last_time.'" limit '.$limit;
        $data = $this->pdo->query($sql);

        if (!$data)
            return null;
        $this->setLastTime($data[count($data)-1]["event_time"]);
        return $data;
    }

    public function setLastTime($time)
    {
        $this->cache->set("wing-binlog-general-log-last-time", $time);
        $this->last_time = $time;
    }
    public function getLastTime()
    {
        $time = $this->cache->get("wing-binlog-general-log-last-time");
        if ($time)
            return $time;
        return date("Y-m-d 00:00:00");
    }

    public function setReadSize($size)
    {
        return $this->cache->set("wing-binlog-general-log-last-read", $size);
    }

    public function getReadSize()
    {
        $size = $this->cache->get("wing-binlog-general-log-last-read");
        if ($size)
            return $size;
        return 0;
    }

}