<?php namespace Wing\Binlog\Library;
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
 * $bin = new \Wing\Binlog\Library\BinLog(
        new \Wing\Binlog\Library\PDO("root","123456","localhost","activity")
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

//        $cache_dir  = $this->getCacheDir();
//        $dir        = new WDir( $cache_dir );
//        $cache_file = $this->getLastBinLogCacheFile();
//        $file       = new WFile( $cache_file );
//
//        $dir->mkdir();

        return Context::instance()->redis->set("mysql:last:binlog",$binlog);

        //return $file->write($binlog,false);
    }

    /**
     * @获取最后操作的binlog文件名称
     */
    private function getLastBinLog(){

        return Context::instance()->redis->get("mysql:last:binlog");//,$binlog);


//        $cache_file = $this->getLastBinLogCacheFile();
//        $file       = new WFile( $cache_file );
//
//        if( !$file->exists() )
//            return "";
//
//        return $file->read();
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
//        $cache_dir  = $this->getCacheDir();
//        $dir        = new WDir( $cache_dir );
//        $cache_file = $this->getLastPositionCacheFile();
//        $file       = new WFile( $cache_file );
//
//        $dir->mkdir();

        return Context::instance()->redis->set("mysql:binlog:lastpos:read", $start_pos.":".$end_pos );

        //return $file->write( $start_pos.":".$end_pos, false );
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
     * @获取数据表行
     *
     * @return array
     */
    protected function getColumns($database_name,$table_name){
        $sql     = 'SHOW COLUMNS FROM `' . $database_name . '`.`' . $table_name . '`';
        $columns = $this->db_handler->query($sql);

        if (!$columns)
        {
            echo "查询数据表行失败\r\n";
            return null;
        }
        $columns = array_column($columns, "Field");
        return $columns;
    }


    /**
     * @获取事件类型
     *
     * @return string
     */
    protected function getEventType( $item ){
        preg_match("/\s(Delete_rows|Write_rows|Update_rows):/", $item, $ematch);

        if (!isset($ematch[1]))
        {
            echo "无法匹配事件\r\n";
            return false;
        }

        return strtolower($ematch[1]);
    }

    /**
     * @获取数据表
     *
     * @return array
     */
    protected function getTables($item){

        preg_match_all("/`[\s\S].*?`.`[\s\S].*?`/", $item, $match_tables);

        if (!isset($match_tables[0][0])) {
            echo "无法匹配数据库和表\r\n";
            return [false,false];
        }

        list($database_name,$table_name) = explode(".",$match_tables[0][0]);

        $database_name = trim($database_name,"`");
        $table_name    = trim($table_name,"`");

        return [$database_name,$table_name];
    }

    /**
     * @获取事件发生的时间
     *
     * @return string
     */
    protected function getEventTime($item){
        preg_match_all("/[0-9]{6}\s+?[0-9]{1,2}\:[0-9]{1,2}\:[0-9]{1,2}/", $item, $time_match);
        if (!isset($time_match[0][0]))
        {
            echo "无法匹配时间\r\n";
            return false;
        }

        $daytime = date("Y-m-d H:i:s", strtotime(substr(date("Y"), 0, 2) . $time_match[0][0]));
        return $daytime;
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


        $lines        = explode("\n", $item);
        $target_lines = [];
        $temp_lines   = [];

        //因为一个事务可能有多个增删改查的操作 为了得到完整的sql信息
        //这里需要分组 - 支持批量和单次操作的binlog
        foreach ($lines as $target_line) {

            $target_line = ltrim($target_line, "#");
            $target_line = trim($target_line);
            $key         = strtolower(substr($target_line, 0, 6));

            //遇到delete、update和insert关键字结束一组
            if ($key == "delete" || $key == "update" || $key == "insert") {
                if ($temp_lines) {
                    $target_lines[] = $temp_lines;
                }
                $temp_lines = [];
            }

            $temp_lines[] = $target_line;
        }

        unset($lines);

        //别忘了最后一组
        $target_lines[] = $temp_lines;

        unset($temp_lines);
        return $target_lines;
    }

    /**
     * @事件数据格式化
     *
     * @return array
     */
    protected function eventDatasFormat( $target_lines, $daytime, $event_type, $columns ){

        //$events     = [];
        $event_data = [
            "event_type" => $event_type,
            "time"       => $daytime
        ];

        // foreach ($target_lines as $_target_line) {
        $is_old_data = true;
        $old_data    = [];
        $new_data    = [];
        $set_data    = [];
        $index       = 0;
        var_dump($target_lines);
        foreach ($target_lines as $target_line) {
            //去掉行的开始#和空格
            $target_line = ltrim($target_line, "#");
            $target_line = trim($target_line);
            echo "========>",$target_line,"\r\n";
            //所有的字段开始的字符都是@
            if (substr($target_line, 0, 1) == "@") {
                $target_line = preg_replace("/@[0-9]{1,}=/", "", $target_line);
                $target_line = trim($target_line, "'");
                //如果是update操作 有两组数据 一组是旧数据 一组是新数据
                if ($event_type == "update_rows") {
                    if ($is_old_data) {
                        $old_data[$columns[$index]] = $target_line;
                    } else {
                        $new_data[$columns[$index]] = $target_line;
                    }
                }
                else {
                    $set_data[$columns[$index]] = $target_line;
                }
                $index++;
            }

            //遇到set关键字 重置索引 开始记录老数据
            if (strtolower($target_line) == "set") {
                $is_old_data = false;
                $index = 0;
            }
        }

        if ($event_type == "update_rows") {
            //这里忽略空数据
            if (count($old_data) <= 0 || count($new_data) <= 0) {
                echo $event_type,"数据为空\r\n";
                return null;
            }

            foreach ($columns as $column ){
                if(!isset($old_data[$column])){
                    echo $column,"--old_data《----行数据异常----》\r\n";
                }
                if(!isset($new_data[$column])){
                    echo $column,"--new_data《----行数据异常----》\r\n";
                }
            }

            $event_data["data"] = [
                "old_data" => $old_data,
                "new_data" => $new_data
            ];
        }
        else {
            //这里忽略空数据
            if (count($set_data) <= 0) {
                echo $event_type,"数据为空\r\n";
                return null;
            }

            foreach ($columns as $column ){
                if(!isset($set_data[$column])){
                    echo $column,"--set_data《----行数据异常----》\r\n";
                }
            }
            $event_data["data"] = $set_data;
        }

        //$events[] = $event_data;
        // }

        return $event_data;
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
     * @获取session元数据
     *
     * @return array
     */
    protected function getSessions( $start_pos, $end_pos ){
        //当前使用的binlog文件路径
        $current_binlog_file = $this->getCurrentLogFile();

        $command   = $this->mysqlbinlog . " --base64-output=DECODE-ROWS -v --start-position=" .
            $start_pos . " --stop-position=" .
            $end_pos . "  \"" . $current_binlog_file . "\"";// > ".$this->binlog_cache_file->get();// >d:\1.sql
        //一个完整的事务 以BEGIN开始COMMIT结束
        $res       = (new Command($command))->run();


        echo "==============================================================\r\n";
        echo $res,"\r\n\r\n\r\n";

        return $res;

        $matches    = explode("BEGIN\n/*!*/;", $res);
        var_dump($matches);
        $commit_res = [];

        foreach ( $matches as $m)
        {
            if( strpos($m,"COMMIT/*!*/;") !== false )
                $_commit_res = explode("COMMIT/*!*/;", $m);
            else
                $_commit_res = explode("COMMIT\n/*!*/;", $m);

            if( count($_commit_res) != 2 )
                continue;
            $commit_res[] = $_commit_res[0];
            $commit_res[] = $_commit_res[1];

        }

        return $commit_res;
    }

    /**
     * @binlog发生改变、事件监控
     *
     * @param \Closure $callback 闭包回调函数
     * @return void
     */
    public function onChange( $callback ){
        while( 1 )
        {
            clearstatcache();
            ob_start();

            try {
                $mem_start = memory_get_usage();

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

                    //得到所有的binlog事件 记住这里不允许加limit 有坑
                    $data = $this->getEvents($current_binlog,$last_end_pos);
                    if (!$data) {
                        break;
                    }

                    //第一行最后一行 完整的事务
                    $last_row  = $data[count($data) - 1];
                    $first_row = $data[0];

                    unset($data);

                    //设置最后读取的位置
                    $this->setLastPosition($first_row["Pos"], $last_row["End_log_pos"]);


                    $start_pos  = $first_row["Pos"];
                    $end_pos    = $last_row["End_log_pos"];
                    $commit_res = $this->getSessions( $start_pos, $end_pos );

                    var_dump($commit_res);
                    // array_map(function ($__item) use ($callback) {
                    $items = $this->linesFormat($commit_res);
                    array_map(function($item)  use ($callback){
                        echo "item===>";
                        var_dump($item);
                        do {
                            //得到事件发生的时间
                            $daytime = $this->getEventTime( $item );
                            if( !$daytime ){
                                break;
                            }
                            echo "事件发生的时间=>",$daytime,"\r\n";

                            //得到事件发生的数据库和表
                            list($database_name,$table_name) = $this->getTables($item);
                            if( !$database_name || !$table_name ) {
                                break;
                            }
                            echo "数据库和数据表=>",$database_name,"=>",$table_name,"\r\n";


                            //得到事件 类型 这里只在乎 Delete_rows|Write_rows|Update_rows
                            //因为这三种事件影响了数据，也就是数据发生了变化
                            $event_type = $this->getEventType( $item );
                            if ( !$event_type ) {
                                break;
                            }
                            echo "事件=>",$event_type,"\r\n";


                            //得到表字段
                            $columns = $this->getColumns( $database_name, $table_name );
                            if (!$columns) {
                                break;
                            }
                            echo "数据表行";
                            var_dump($columns);
                            echo "\r\n";

                            //按行解析
                            //因为一个事务可能有多个增删改查的操作 为了得到完整的sql信息
                            //这里需要分组 - 支持批量和单次操作的binlog
                            //$target_lines = $this->linesFormat($item);
                            $target_lines = explode("\n",$item);
                            $event       = $this->eventDatasFormat( $target_lines, $daytime, $event_type, $columns );

                            unset($target_lines);
                            echo "events===>";
                            var_dump($event);

                            // foreach ( $events as $event ){
                            //事件计数器
                            if( $event ) {
                                $this->events_times++;
                                $str1 = md5(rand(0,999999));
                                $str2 = md5(rand(0,999999));
                                $str3 = md5(rand(0,999999));

                                $event["__enevt_id"] = "seals_".time()."_".
                                    substr($str1,rand(0,strlen($str1)-16),16)."_".
                                    substr($str2,rand(0,strlen($str2)-16),16)."_".
                                    substr($str3,rand(0,strlen($str3)-16),16);
                                //执行事件回调函数
                                $callback($database_name, $table_name, $event);
                                echo "事件次数", $this->events_times, "\r\n\r\n";
                            }//  }

                            unset($events);
                        } while (0);
                    },$items);
                    // }, $commit_res );

                    // unset($commit_res);


                } while (0);


                $this->runEventCallback(self::EVENT_TICK_END);

                $mem_end = memory_get_usage();

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