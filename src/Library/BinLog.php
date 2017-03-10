<?php namespace Seals\Library;
use Wing\FileSystem\WDir;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/10
 * Time: 10:23
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

    /**
     * @var bool
     */
    private $debug       = false;

    private $host;
    private $password;
    private $user;
    private $port = 3306;

    /**
     * 构造函数
     *
     * @param DbInterface $db_handler
     * @param string $mysqlbinlog
     */
    public function __construct(DbInterface $db_handler,$mysqlbinlog = "mysqlbinlog")
    {
        $this->db_handler  = $db_handler;
        $this->mysqlbinlog = $mysqlbinlog;

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

    /**
     * 设置debug
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * 设置mysqlbinlog命令路径
     *
     * @param string $mysqlbinlog
     */
    public function setMysqlbinlog($mysqlbinlog)
    {
        $this->mysqlbinlog = $mysqlbinlog;
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
        $sql  = 'show master status';
        $data = $this->db_handler->row($sql);
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
        $sql  = 'select @@log_bin_basename';
        $data = $this->db_handler->row($sql);
        $path = pathinfo($data["@@log_bin_basename"],PATHINFO_DIRNAME);
        $info = $this->getCurrentLogInfo();

        return $path.DIRECTORY_SEPARATOR.$info["File"];
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
        return file_put_contents(dirname(dirname(__DIR__))."/mysql.last",$binlog);
    }

    /**
     * 获取最后操作的binlog文件名称
     *
     * @return string
     */
    public function getLastBinLog()
    {
        return file_get_contents(dirname(dirname(__DIR__))."/mysql.last");
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
        return file_put_contents(dirname(dirname(__DIR__))."/mysql.pos",$start_pos.":".$end_pos);
    }

    /**
     * 获取最后的读取位置
     *
     * @return array
     */
    public function getLastPosition()
    {
        $pos = file_get_contents(dirname(dirname(__DIR__))."/mysql.pos");
        $res = explode(":",$pos);
        if (!is_array($res) || count($res) != 2)
            return [0,0];
        return $res;
    }

    /**
     * 获取binlog事件，请只在意第一第二个参数
     *
     * @return array
     */
    public function getEvents($current_binlog,$last_end_pos, $limit = 10000)
    {
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
        $command    = $this->mysqlbinlog .
            " -u".$this->user.
            " -p".$this->password.
            " -h".$this->host.
            " -P".$this->port.
            " --read-from-remote-server".
            " --base64-output=DECODE-ROWS -v".
            " --start-position=" . $start_pos .
            " --stop-position=" . $end_pos .
            "  \"" . $current_binlog_file . "\" > ".$cache_file;


        $command    = $this->mysqlbinlog . " --base64-output=DECODE-ROWS -v --start-position=" .
            $start_pos . " --stop-position=" .
            $end_pos . "  \"" . $current_binlog_file . "\" > ".$cache_file ;

        unset($current_binlog_file);

        echo $command,"\r\n";
        system($command);
        /*$handle = popen($command,"w");
        fputs($handle,$this->password);
        fclose($handle);*/
//
//        global $STDIN;
//        fwrite($STDIN,$this->password);

        //////////////////
     /*   $descriptorspec = array(
            0 => array("pipe", "r"),  // 标准输入，子进程从此管道中读取数据
            1 => array("pipe", "w"),  // 标准输出，子进程向此管道中写入数据
            2 => array("file", $this->log_dir."/mysqlbinlog_error.log", "a+") // 标准错误，写入到一个文件
        );

        $cwd = null;//'/tmp';
        $env = null;//array('some_option' => 'aeiou');


        $process = proc_open($command, $descriptorspec, $pipes, $cwd, $env);

            if (is_resource($process)) {
                // $pipes 现在看起来是这样的：
                // 0 => 可以向子进程标准输入写入的句柄
                // 1 => 可以从子进程标准输出读取的句柄
                // 错误输出将被追加到文件 /tmp/error-output.txt

//                foreach ($pipes as $pipe) {
//                    stream_set_blocking($pipe, 0);
//                }
                fwrite($pipes[0], $this->password);
                fclose($pipes[0]);
                fclose($pipes[1]);
                proc_close($process);

            }
*/
        ////////////////////

        unset($command);
        return $cache_file;
    }
}