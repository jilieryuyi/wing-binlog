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
    public function __construct(DbInterface $pdo)
    {
        $this->pdo = $pdo;
        $this->cache = new File(__APP_DIR__);

        $last_time = $this->cache->get("general.last");
        if ($last_time)
            $this->last_time = $last_time;
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
        $sql  = 'select @@general_log_file';
        $data = $this->pdo->row($sql);

        if (!isset($data["@@general_log_file"]))
            return null;

        return $data["@@general_log_file"];
    }
    public function isOpen()
    {
        $sql = 'select @@general_log';
        $data = $this->pdo->row($sql);

        return isset($data["@@general_log"]) && $data["@@general_log"] == 1;
    }

    public function logOutput()
    {
        $sql   = 'select @@log_output';
        $row   = $this->pdo->row($sql);
        $type1 = $row["@@log_output"];
        if (strpos($row["@@log_output"],",") !== false) {
            list($type1, $type2) = explode(",", $row["@@log_output"]);
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
        //$this->last_time = $data[count($data)-1]["event_time"];
        $this->setLastTime($data[count($data)-1]["event_time"]);
        return $data;
    }

    public function setLastTime($time)
    {
        $this->cache->set("general.last",$time);
        $this->last_time = $time;
    }

}