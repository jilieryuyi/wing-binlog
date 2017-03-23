<?php namespace Seals\Library;
use Seals\Cache\File;
use Wing\FileSystem\WDir;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/10
 * Time: 10:23
 * @property CacheInterface $cache_handler
 */
class BinLog
{
    /**
     * @var DbInterface
     */
    private $db_handler;

    /**
     * mysqlbinlog 命令路径
     * @var string
     */
    private $mysqlbinlog  = "mysqlbinlog";

    /**
     * @var string
     */
    private $cache_dir;
    private $log_dir;
    private $cache_handler;

    /**
     * @var bool
     */
    private $debug       = false;

    private $host;
    private $password;
    private $user;
    private $port = 3306;
    private $cache;

    /**
     * 构造函数
     *
     * @param DbInterface $db_handler
     * @param string $mysqlbinlog
     */
    public function __construct(DbInterface $db_handler)
    {
        $this->db_handler  = $db_handler;
        $this->mysqlbinlog = Context::instance()->mysqlbinlog_bin;

        if (!$this->isOpen()) {
            echo "请开启mysql binlog日志\r\n";
            exit;
        }

        if ($this->getFormat() != "row") {
            echo "仅支持row格式\r\n";
            exit;
        }

        $this->host     = $db_handler->getHost();
        $this->user     = $db_handler->getUser();
        $this->password = $db_handler->getPassword();
        $this->port     = $db_handler->getPort();
        $this->log_dir  = Context::instance()->getAppConfig("log_dir");
        $this->cache    = new File(__APP_DIR__."/data/table");
    }


    /**
     * 设置缓存目录
     *
     * @param string $dir
     */
    public function setCacheDir($dir)
    {
        $dir = str_replace("\\","/",$dir);
        $dir = rtrim($dir,"/");

        $this->cache_dir = $dir;

        $dir = new WDir($this->cache_dir);
        $dir->mkdir();

        if (!$dir->isWrite()) {
            die($this->cache_dir ." is not writeable \r\n");
        }

        unset($dir);
    }

    public function setCacheHandler(CacheInterface $cache)
    {
        $this->cache_handler = $cache;
    }

    /**
     * 设置debug
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }


    /**
     * 获取所有的logs
     *
     * @return array
     */
    public function getLogs()
    {
        $sql  = 'show binary logs';
        return $this->db_handler->query($sql);
    }

    public function getFormat()
    {
        $sql  = 'select @@binlog_format';
        $data = $this->db_handler->row($sql);
        return strtolower($data["@@binlog_format"]);
    }

    /**
     * 获取当前正在使用的binglog日志文件信息
     *
     * @return array 一维
     *    array(5) {
     *           ["File"] => string(16) "mysql-bin.000005"
     *           ["Position"] => int(8840)
     *           ["Binlog_Do_DB"] => string(0) ""
     *           ["Binlog_Ignore_DB"] => string(0) ""
     *           ["Executed_Gtid_Set"] => string(0) ""
     *     }
     */
    public function getCurrentLogInfo()
    {
        $key  = "show.master.status.table";
        $data = $this->cache->get($key);
        if ($data && is_array($data)) {
            return $data;
        }

        $sql  = 'show master status';
        $data = $this->db_handler->row($sql);
        $this->cache->set($key, $data, 60);
        return $data;
    }

    /**
     * 获取所有的binlog文件
     *
     * @return array
     */
    public function getFiles()
    {
        $logs  = $this->getLogs();
        $sql   = 'select @@log_bin_basename';
        $data  = $this->db_handler->row($sql);
        $path  = pathinfo($data["@@log_bin_basename"],PATHINFO_DIRNAME);
        $files = [];

        foreach ($logs as $line) {
            $files[] = $path.DIRECTORY_SEPARATOR.$line["Log_name"];
        }

        return $files;
    }

    /**
     * 获取当前正在使用的binlog文件路径
     *
     * @return string
     */
    public function getCurrentLogFile()
    {
        $key  = "select.log_bin_basename.table";
        $path = $this->cache->get($key);
        if ($path) {
            return $path;
        }

        $sql  = 'select @@log_bin_basename';
        $data = $this->db_handler->row($sql);
        $path = pathinfo($data["@@log_bin_basename"],PATHINFO_DIRNAME);
        $info = $this->getCurrentLogInfo();

        $path = $path.DIRECTORY_SEPARATOR.$info["File"];
        $this->cache->set($key, $path, 60);
        return $path;
    }

    /**
     * 检测是否已开启binlog功能
     *
     * @return bool
     */
    public function isOpen()
    {
        $sql  = 'select @@sql_log_bin';
        $data = $this->db_handler->row($sql);
        return isset($data["@@sql_log_bin"]) && $data["@@sql_log_bin"] == 1;
    }


    /**
     * 设置存储最后操作的binlog名称--游标，请勿删除mysql.last
     *
     * @param string $binlog
     */
    public function setLastBinLog($binlog)
    {
        return $this->cache_handler->set("mysql.last", $binlog);
    }

    /**
     * 获取最后操作的binlog文件名称
     *
     * @return string
     */
    public function getLastBinLog()
    {
        return $this->cache_handler->get("mysql.last");
    }

    /**
     * 设置最后的读取位置--游标，请勿删除mysql.pos
     *
     * @param int $start_pos
     * @param int $end_pos
     * @return bool
     */
    public function setLastPosition($start_pos,$end_pos)
    {
        return $this->cache_handler->set("mysql.pos", [$start_pos,$end_pos]);
    }

    /**
     * 获取最后的读取位置
     *
     * @return array
     */
    public function getLastPosition()
    {
        return $this->cache_handler->get("mysql.pos");
    }

    /**
     * 获取binlog事件，请只在意第一第二个参数
     *
     * @return array
     */
    public function getEvents($current_binlog,$last_end_pos, $limit = 10000)
    {
        if (!$last_end_pos)
            $last_end_pos = 0;

        $sql   = 'show binlog events in "' . $current_binlog . '" from ' . $last_end_pos.' limit '.$limit;
        $datas = $this->db_handler->query($sql);

        if ($datas) {
            echo $sql,"\r\n";
        }

        return $datas;
    }

    /**
     * 获取session元数据--直接存储于cache_file
     *
     * @return string 缓存文件路径
     */
    public function getSessions($start_pos, $end_pos)
    {
        //当前使用的binlog文件路径
        $current_binlog_file = $this->getCurrentLogFile();

        $str1 = md5(rand(0,999999));
        $str2 = md5(rand(0,999999));
        $str3 = md5(rand(0,999999));

        $cache_file  = $this->cache_dir."/seals_".time().
            substr($str1,rand(0,strlen($str1)-16),16).
            substr($str2,rand(0,strlen($str2)-16),16).
            substr($str3,rand(0,strlen($str3)-16),16);

        unset($str1,$str2,$str3);

        //mysqlbinlog -uroot -proot -h127.0.0.1 -P3306 --read-from-remote-server mysql-bin.000001 --base64-output=decode-rows -v > 1
        /*$command    = $this->mysqlbinlog .
            " -u".$this->user.
            " -p\"".$this->password."\"".
            " -h".$this->host.
            " -P".$this->port.
            //" --read-from-remote-server".
            " -R --base64-output=DECODE-ROWS -v". //-vv
            " --start-position=" . $start_pos .
            " --stop-position=" . $end_pos .
            "  \"" . $current_binlog_file . "\" > ".$cache_file;
       */
       // echo preg_replace("/\-p[\s\S]{1,}?\s/","-p****** ",$command,1),"\r\n";
        $command    =
            $this->mysqlbinlog .
            " --base64-output=DECODE-ROWS -v".
            " --start-position=" . $start_pos .
            " --stop-position=" . $end_pos . "  \"" . $current_binlog_file . "\" > ".$cache_file ;

        echo $command,"\r\n";

        unset($current_binlog_file);
        system($command);

        unset($command);
        return $cache_file;
    }
}