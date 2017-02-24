<?php namespace Seals\Library;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;

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
    private $events_times = 0;

     const EVENT_TICK_START = "on_tick_start";
     const EVENT_TICK_END   = "on_tick_end";

    private $cache_dir;

    private $callbacks = [];

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
        //防止在导入、删除大量数据时候发生内存错误 这里调整内存限制为10G
        ini_set("memory_limit","10240M");
        $dir = new WDir($this->getCacheDir());
        $dir->mkdir();

        unset($dir);

        $this->cache_dir = $this->getCacheDir();

    }

    /**
     * @绑定回调函数，支持绑定多个回调函数
     *
     * @param string $event 事件名称
     * @param \Closure $callback 事件回调函数
     */
    public function setEventCallback( $event, $callback ){
        if( isset($this->callbacks[$event]) ) {
            $old = $this->callbacks[$event];
            $this->callbacks[$event][] = $old;
            $this->callbacks[$event][] = $callback;
        }else{
            $this->callbacks[$event] = $callback;
        }
    }

    /**
     * @指定绑定的回调函数
     *
     * @param string $event 事件名称
     */
    private function runEventCallback($event){
        if( !is_array($this->callbacks[$event]) ){
            if( is_callable($this->callbacks[$event]) )
                call_user_func($this->callbacks[$event]);
        }else{
            array_map(function($callback){
                call_user_func($callback);
            },$this->callbacks[$event]);
        }
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
     * @获取缓存目录，需要保证此目录有可写权限
     *
     * @return string
     */
    private function getCacheDir(){
        return dirname(dirname(__DIR__))."/cache";
    }

    /**
     * @获取binlog缓存文件
     *
     * @return string
     */
    private function getLastBinLogCacheFile(){
        $cache_dir  = $this->getCacheDir();
        return $cache_dir.DIRECTORY_SEPARATOR."mysql_last_bin_log";
    }

    /**
     * @设置存储最后操作的binlog名称
     */
    private function setLastBinLog( $binlog ){
        return Context::instance()->redis->set("mysql:last:binlog",$binlog);
    }

    /**
     * @获取最后操作的binlog文件名称
     */
    private function getLastBinLog(){
        return Context::instance()->redis->get("mysql:last:binlog");//,$binlog);
    }

    /**
     * @获取记录记录上次binlog读取位置的缓存文件
     *
     * @return string
     */
    private function getLastPositionCacheFile(){
        $cache_dir  = $this->getCacheDir();
        return $cache_dir.DIRECTORY_SEPARATOR."mysql_last_bin_log_pos";
    }

    /**
     * @设置最后的读取位置
     *
     * @param int $start_pos
     * @param int $end_pos
     * @return bool
     */
    private function setLastPosition( $start_pos, $end_pos ){
        return Context::instance()->redis->set("mysql:binlog:lastpos:read", $start_pos.":".$end_pos );
    }

    /**
     * @获取最后的读取位置
     * @return array
     */
    private function getLastPosition(){
//        $file_path = $this->getLastPositionCacheFile() ;
//        $file      = new WFile( $file_path );
//        if( !$file->exists() )
//            return [0,0];
        $res = explode(":", Context::instance()->redis->get("mysql:binlog:lastpos:read") );
        if( !is_array($res) || count($res) != 2 )
            return [0,0];
        return $res;
    }






    /**
     * @行分组格式化
     *
     * @return array
     */
    protected function linesFormat($item){
        $items = preg_split("/#[\s]{1,}at[\s]{1,}[0-9]{1,}/",$item);
        echo "new-----items---";
        var_dump($items);
        return $items;
    }



    /**
     * @获取binlog事件
     *
     * @return array
     */
    protected function getEvents($current_binlog,$last_end_pos){
        $sql  = 'show binlog events in "' . $current_binlog . '" from ' . $last_end_pos;// .' limit 10';
        return $this->db_handler->query($sql);
    }


    /**
     * @获取session元数据--直接存储于cache_file
     *
     * @return string cache_file path
     */
    protected function getSessions( $start_pos, $end_pos ){
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
            $end_pos . "  \"" . $current_binlog_file . "\" > ".$cache_file ;// > ".$this->binlog_cache_file->get();// >d:\1.sql

        unset($current_binlog_file);

        pclose(popen( $command ,"r"));

        return $cache_file;
    }

    /**
     * @binlog发生改变、事件监控
     *
     * @return void
     */
    public function dispatch( $callback ){
        while( 1 )
        {
            clearstatcache();
            ob_start();

            try {
                //$mem_start = memory_get_usage();

                do {
                    $this->runEventCallback(self::EVENT_TICK_START);

                    //最后操作的binlog文件
                    $last_binlog         = $this->getLastBinLog();
                    //当前使用的binlog 文件
                    $current_binlog      = $this->getCurrentLogInfo()["File"];

                    //获取最后读取的位置
                    list($last_start_pos, $last_end_pos) = $this->getLastPosition();

                    //binlog切换时，比如 .00001 切换为 .00002，重启mysql时会切换
                    //重置读取起始的位置
                    if ($last_binlog != $current_binlog) {
                        $this->setLastBinLog($current_binlog);
                        $last_start_pos = $last_end_pos = 0;
                        $this->setLastPosition($last_start_pos, $last_end_pos);
                    }

                    unset($last_binlog);

                    //得到所有的binlog事件 记住这里不允许加limit 有坑
                    $data = $this->getEvents($current_binlog,$last_end_pos);
                    if (!$data) {
                        unset($current_binlog,$last_start_pos,$last_start_pos);
                        break;
                    }
                    unset($current_binlog,$last_start_pos,$last_start_pos);

                    //第一行最后一行 完整的事务
                    $last_row  = $data[count($data) - 1];
                    $first_row = $data[0];

                    unset($data);

                    //设置最后读取的位置
                    $this->setLastPosition($first_row["Pos"], $last_row["End_log_pos"]);


                    $start_pos  = $first_row["Pos"];
                    $end_pos    = $last_row["End_log_pos"];

                    unset($first_row,$last_row);


                    $cache_path = $this->getSessions( $start_pos, $end_pos );
                    unset($end_pos,$start_pos);

                    //进程调度 看看该把cache_file扔给那个进程处理
                    $callback($cache_path);

                    unset($cache_path);

                } while (0);


                $this->runEventCallback(self::EVENT_TICK_END);

                //$mem_end = memory_get_usage();

            }catch(\Exception $e){
                var_dump($e);
            }

            $output = ob_get_contents();
            ob_end_clean();

            if ($output) {
                echo $output;
            }
            usleep(100000);


            // echo "增加了=>",($mem_end - $mem_start),"\r\n";

        }
    }




}