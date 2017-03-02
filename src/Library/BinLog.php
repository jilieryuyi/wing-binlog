<?php namespace Seals\Library;
use Wing\FileSystem\WDir;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/10
 * Time: 10:23
 *
 * @mysql数据变化监控实现，cache默认使用redis
 * @demo
 * $bin = new \Seals\Library\BinLog(
        new \Seals\Library\PDO("root","123456","localhost","activity")
    );
    $bin->onChange(function( $database_name, $table_name, $event_data ){
        echo "数据库：",$database_name,"\r\n";
        echo "数据表：",$table_name,"\r\n";
        echo "改变数据：";var_dump($event_data);
        echo "\r\n\r\n\r\n";
    });
 */
class BinLog{

    private $db_handler;
    //mysqlbinlog 命令路径
    private $mysqlbinlog  = "mysqlbinlog";

    private $cache_dir;

    private $debug       = false;

    public function __construct( DbInterface $db_handler,$mysqlbinlog = "mysqlbinlog")
    {
        $this->db_handler  = $db_handler;
        $this->mysqlbinlog = $mysqlbinlog;

        if( !$this->isOpen() )
        {
            echo "请开启mysql binlog日志\r\n";
            exit;
        }

        if( $this->getFormat() != "row" )
        {
            echo "仅支持row格式\r\n";
            exit;
        }
    }

    public function setCacheDir($dir){
        $dir = str_replace("\\","/",$dir);
        $dir = rtrim($dir,"/");
        $this->cache_dir = $dir;
        $dir = new WDir($this->cache_dir);
        $dir->mkdir();
        if( !$dir->isWrite() ) {
            die( $this->cache_dir ." is not writeable \r\n" );
        }
        unset($dir);
    }

    public function setDebug( $debug ){
        $this->debug = $debug;
    }

    /**
     * @设置mysqlbinlog命令路径
     */
    public function setMysqlbinlog( $mysqlbinlog ){
        $this->mysqlbinlog = $mysqlbinlog;
    }

    /**
     * @获取所有的logs
     *
     * @return array
     */
    public function getLogs(){
        $sql  = 'show binary logs';
        return $this->db_handler->query( $sql );
    }

    public function getFormat(){
        $sql = 'select @@binlog_format';
        $data = $this->db_handler->row( $sql );
        return strtolower( $data["@@binlog_format"] );
    }

    /**
     * @获取当前正在使用的binglog日志文件信息
     *
     * @return array 一维
     *    array(5) {
                ["File"] => string(16) "mysql-bin.000005"
                ["Position"] => int(8840)
                ["Binlog_Do_DB"] => string(0) ""
                ["Binlog_Ignore_DB"] => string(0) ""
                ["Executed_Gtid_Set"] => string(0) ""
          }

     */
    public function getCurrentLogInfo(){
        $sql  = 'show master status';
        $data = $this->db_handler->row( $sql );
        return $data;
    }

    /**
     * @获取所有的binlog文件
     */
    public function getFiles(){

        $logs  = $this->getLogs();
        $sql   = 'select @@log_bin_basename';
        $data  = $this->db_handler->row( $sql );
        $path  = pathinfo( $data["@@log_bin_basename"],PATHINFO_DIRNAME );
        $files = [];

        foreach ( $logs as $line )
        {
            $files[] = $path.DIRECTORY_SEPARATOR.$line["Log_name"];
        }

        return $files;
    }

    /**
     * @获取当前正在使用的binlog文件路径
     */
    public function getCurrentLogFile(){

        $sql  = 'select @@log_bin_basename';
        $data = $this->db_handler->row( $sql );
        $path = pathinfo( $data["@@log_bin_basename"],PATHINFO_DIRNAME );
        $info = $this->getCurrentLogInfo();

        return $path.DIRECTORY_SEPARATOR.$info["File"];
    }

    /**
     * @检测是否已开启binlog功能
     *
     * @return bool
     */
    public function isOpen(){
        $sql  = 'select @@sql_log_bin';
        $data = $this->db_handler->row( $sql );
        return isset( $data["@@sql_log_bin"] ) && $data["@@sql_log_bin"] == 1;
    }


    /**
     * @设置存储最后操作的binlog名称
     */
    public function setLastBinLog( $binlog ){
        return file_put_contents(dirname(dirname(__DIR__))."/mysql.last",$binlog);
    }

    /**
     * @获取最后操作的binlog文件名称
     */
    public function getLastBinLog(){
        return file_get_contents(dirname(dirname(__DIR__))."/mysql.last");
    }

    /**
     * @设置最后的读取位置
     *
     * @param int $start_pos
     * @param int $end_pos
     * @return bool
     */
    public function setLastPosition( $start_pos, $end_pos ){
        return file_put_contents(dirname(dirname(__DIR__))."/mysql.pos",$start_pos.":".$end_pos);
    }

    /**
     * @获取最后的读取位置
     * @return array
     */
    public function getLastPosition(){
        $pos = file_get_contents(dirname(dirname(__DIR__))."/mysql.pos");
        $res = explode(":",$pos);
        if( !is_array($res) || count($res) != 2 )
            return [0,0];
        return $res;
    }

    /**
     * @获取binlog事件，请只在意第一第二个参数
     *
     * @return array
     */
    public function getEvents($current_binlog,$last_end_pos, $limit = 10000){
        $sql   = 'show binlog events in "' . $current_binlog . '" from ' . $last_end_pos.' limit '.$limit;
        $datas = $this->db_handler->query($sql);
        return $datas;
    }

    /**
     * @获取session元数据--直接存储于cache_file
     *
     * @return string cache_file path
     */
    public function getSessions( $start_pos, $end_pos ){
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

        $command    = $this->mysqlbinlog . " --base64-output=DECODE-ROWS -v --start-position=" .
            $start_pos . " --stop-position=" .
            $end_pos . "  \"" . $current_binlog_file . "\" > ".$cache_file ;

        unset($current_binlog_file);

        system($command);

        unset($command);
        return $cache_file;
    }




}