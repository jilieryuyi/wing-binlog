<?php namespace Seals\Library;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;

/**
 * @author yuyi
 * @created 2016/9/23 8:27
 * @email 297341015@qq.com
 * @property Notify $notify
 */

class Worker implements Process{


    protected $work_dir;
    protected $log_dir;
    protected $debug            = false;
    protected $start_time       = 0;
    protected $workers          = 1;
    protected $notify;
    protected $cache_dir;
    protected $app_id           = "wing-binlog";
    protected $memory_limit     = "10240M";
    protected $binlog_cache_dir;


    //队列名称
    const QUEUE_NAME = "seals:events:collector";

    /**
     * @构造函数
     */
    public function __construct(
        $app_id = "",
        $memory_limit = "10240M",
        $log_dir = __APP_DIR__."/logs",
        $process_cache_dir = __APP_DIR__."/process_cache",
        $binlog_cache_dir = __APP_DIR__."/cache"
    )
    {
        gc_enable();

        $this->start_time       = time();
        $this->app_id           = $app_id;
        $this->memory_limit     = $memory_limit;
        $this->binlog_cache_dir = $binlog_cache_dir;

        $this->setWorkDir(dirname(dirname(__DIR__)));
        $this->setLogDir( $log_dir );
        $this->setProcessCacheDir( $process_cache_dir );

        register_shutdown_function(function(){
            file_put_contents( $this->log_dir."/shutdown_".date("Ymd")."_".self::getCurrentProcessId().".log",date("Y-m-d H:i:s")."\r\n".json_encode(error_get_last(),JSON_UNESCAPED_UNICODE)."\r\n\r\n",FILE_APPEND);
            $this->clear();
        });

        set_error_handler(function($errno, $errstr, $errfile, $errline){
            file_put_contents( $this->log_dir."/error_".date("Ymd")."_".self::getCurrentProcessId().".log",date("Y-m-d H:i:s")."\r\n".json_encode(func_get_args(),JSON_UNESCAPED_UNICODE)."\r\n\r\n",FILE_APPEND);
        });

        $cpu = new Cpu();
        $this->setWorkersNum( $cpu->cpu_num ) ;

        unset($cpu);
        ini_set("memory_limit", $this->memory_limit );

    }

    public function __destruct()
    {
        $this->clear();
    }

    /**
     * @事件通知方式实现
     */
    public function setNotify( Notify $notify ){
        $this->notify = $notify;
    }

    /**
     * @设置进程标题，仅linux
     */
    public function setProcessTitle( $title ){
        if( function_exists("setproctitle") )
            setproctitle($title);
        if( function_exists("cli_set_process_title") )
            cli_set_process_title($title);
    }

    //启用debug模式
    public function enabledDebug(){
        $this->debug = true;
        return $this;
    }

    //禁用debug模式
    public function disabledDebug(){
        $this->debug = false;
        return $this;
    }

    /**
     * @设置工作目录
     */
    public function setWorkDir($dir){
        $dir = str_replace("\\","/",$dir);
        $dir = rtrim($dir,"/");
        $this->work_dir = $dir;
        $dir = new WDir($this->work_dir);
        $dir->mkdir();
        unset($dir);
        //改变当前文件目录
        chdir( $this->work_dir );
    }

    public function setProcessCacheDir($dir){
        $dir = str_replace("\\","/",$dir);
        $dir = rtrim($dir,"/");
        $this->cache_dir = $dir;
        $dir = new WDir($this->cache_dir);
        $dir->mkdir();
        unset($dir);
    }

    /**
     * @设置日志目录
     */
    public function setLogDir($dir){
        $dir = str_replace("\\","/",$dir);
        $dir = rtrim($dir,"/");
        $this->log_dir = $dir;
        $dir = new WDir($this->log_dir);
        $dir->mkdir();
        unset($dir);
    }


    /**
     * @退出时清理一些资源
     */
    private function clear(){

        $process_id = self::getCurrentProcessId();
        if( file_exists($this->cache_dir."/running_".$process_id))
            unlink($this->cache_dir."/running_".$process_id);

        if(file_exists($this->cache_dir."/stop_".$process_id))
            unlink($this->cache_dir."/stop_".$process_id);

        if(file_exists($this->cache_dir."/status_".$process_id))
            unlink($this->cache_dir."/status_".$process_id);
    }

    /**
     * @获取模块的名称
     *
     * @return string
     */
    public function getQueueName(){
        return self::QUEUE_NAME;
    }


    /**
     * @设置模块正在运行
     *
     * @return self
     */
    public function setIsRunning(){
        $process_id = self::getCurrentProcessId();
        file_put_contents($this->cache_dir."/running_".$process_id,1);
        return $this;
    }

    /**
     * @获取模块是否正在运行
     *
     * @return bool
     */
    public function getIsRunning(){
        $process_id = self::getCurrentProcessId();
        $cache_file = $this->cache_dir."/running_".$process_id;
        return file_exists($cache_file) && file_get_contents($cache_file) == 1;
    }

    /**
     * @检查退出信号
     *
     * @return void
     */
    public function checkStopSignal(){
        $process_id = self::getCurrentProcessId();
        $cache_file = $this->cache_dir."/stop_".$process_id;
        $is_stop    = file_exists( $cache_file ) && file_get_contents($cache_file) == 1;
        if( $is_stop )
        {
            echo $process_id," get stop signal\r\n";
            exit(0);
        }
    }

    /**
     * @停止模块
     *
     * @return void
     */
    public function stop(){

        $dir   = new WDir($this->cache_dir);
        $files = $dir->scandir();

        $process_ids = [];
        foreach ( $files as $file ){
            $name = pathinfo( $file,PATHINFO_FILENAME);
            list( $name, $process_id) = explode("_",$name);
            if( $name == "running" )
                $process_ids[] = $process_id;
        }

        if( !$process_ids )
            return;

        foreach ( $process_ids as $process_id )
        {
            $cache_file = $this->cache_dir."/stop_".$process_id;
            file_put_contents($cache_file,1);
        }

    }

    /**
     * @获取运行状态
     *
     * @return string
     */
    public function getStatus(){

        $arr   = [];
        $_res  = [];
        $dir   = new WDir($this->cache_dir);
        $files = $dir->scandir();

        foreach ( $files as $file ){
            $name = pathinfo( $file,PATHINFO_FILENAME);
            list( $name, $process_id) = explode("_",$name);
            if( $name == "status" )
                $arr[$process_id] = file_get_contents($file) ;
        }

        foreach ( $arr as $process_id => $josn ){
            $t = json_decode($josn,true);
            if( (time()-$t["updated"]) >= 180 ) {
                unlink($this->cache_dir."/status_".$process_id);
            }
            else {
                $_res[] = $t;
            }
        }

        if( count($_res) <= 0 )
            return "";

        $res = [];
        foreach ($_res as $status) {

            $time_len = timelen_format(time() - $status["start_time"]);
            $n        = preg_replace("/\D/","",$status["name"]);
            $wlen     = 0;

            if( strpos($status["name"],"dispatch") !== false ) {
                $queue = new Queue(self::QUEUE_NAME .":ep". $n, Context::instance()->redis_local);
                $wlen  = $queue->length();
            }
            else {
                $queue = new Queue(self::QUEUE_NAME . $n, Context::instance()->redis_local);
                $wlen  = $queue->length();
            }

            $res[] = [
                "process_id" => sprintf("%-8d",$status["process_id"]),
                "start_time" => date("Y-m-d H:i:s", $status["start_time"]),
                "run_time"   => $time_len,
                "name"       => $status["name"],
                "work_len"   => $wlen
            ];

            if(!is_numeric($n))
                $n = 0;

            if( strpos($status["name"],"dispatch") !== false ) {
                $names[] = $n+1;
            }
            elseif( strpos($status["name"],"workers") !== false ) {
                $names[] = $n+100;
            }
            else{
                $names[] = 0;
            }
        }

        array_multisort( $names, SORT_ASC, $res );

        $str = "进程id    开始时间            待处理任务  运行时长\r\n";
        foreach ($res as $v)
            $str.=  $v["process_id"].
                "  ". $v["start_time"].
                "     ". $v["work_len"].
                "       ". $v["run_time"].
                "  ". $v["name"]."\r\n";
        return $str;

    }

    /**
     * @设置运行状态
     *
     * @return void
     */
    public function setStatus( $name ){
        $process_id = self::getCurrentProcessId();
        $cache_file = $this->cache_dir."/status_".$process_id;

        file_put_contents($cache_file,json_encode([
            "process_id" => $process_id,
            "start_time" => $this->start_time,
            "name"       => $name,
            "updated"    => time()
        ]));

    }

    /**
     * @重置标准错误和标准输出，linux支持
     *
     * @param string $output_name  可选参数，主要为了区分不同进程的输出
     * @throws \Exception
     */
    protected function resetStd()
    {
        if( strtolower( substr(php_uname('s'),0,3) ) == "win" )
        {
            return;
        }

        global $STDOUT, $STDERR;

        $file   = new WFile( $this->log_dir . "/seals_output_".date("Ymd")."_".self::getCurrentProcessId().".log" );
        $file->touch();

        $handle = fopen( $file->get(), "a+");
        if ($handle)
        {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen( $file->get(), "a+");
            $STDERR = fopen( $file->get(), "a+");
        } else
        {
            throw new \Exception('can not open stdoutFile ' . $file->get());
        }
    }

    /**
     * @服务状态
     */
    public function status(){
        return $this->getStatus();
    }

    public function getWorkersNum(){
        return $this->workers;
    }

    public function setWorkersNum($workers){
        $this->workers = $workers;
    }

    /**
     * @是否还在运行
     */
    public function isRunning(){
        return $this->getIsRunning();
    }

    /**
     * @获取当前进程id
     *
     * @return int
     */
    public static function  getCurrentProcessId(){
        if( function_exists("getmypid") )
            return getmypid();

        if( function_exists("posix_getpid"))
            return posix_getpid();
        return 0;
    }

    /**
     * @守护进程化，需要安装pcntl扩展，linux支持
     */
    public static function daemonize()
    {

        if( !function_exists("pcntl_fork") )
            return;
        //创建进程
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new \Exception('fork fail');
        } elseif ($pid > 0) {
            //父进程直接退出
            exit(0);
        }

        //创建进程会话
        if (-1 === posix_setsid()) {
            throw new \Exception("setsid fail");
        }

        //修改掩码
        umask(0);

    }

    public function getWorker( )
    {
        $target_worker = self::QUEUE_NAME. ":ep1";

        //那个工作队列的待处理任务最少 就派发给那个队列
        $num = $this->workers;

        if( $num <= 1 )
        {
            return $target_worker;
        }

        $target_len = Context::instance()->redis_local->lLen($target_worker);

        for ($i = 2; $i <= $num; $i++ ) {
            $len = Context::instance()->redis_local->lLen(self::QUEUE_NAME. ":ep" . $i);
            if ($len < $target_len) {
                $target_worker = self::QUEUE_NAME. ":ep" . $i;
                $target_len    = $len;
            }
        }
        return $target_worker;
    }



    //调度进程
    protected function dispatchProcess( $i  ){

        $self         = $this;
        $process_name = "php seals >> events collector - dispatch - ".$i;

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle( $process_name );

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()->reset();

        $bin = new \Seals\Library\BinLog(
            \Seals\Library\Context::instance()->activity_pdo
        );
        $bin->setWorkers( $this->workers );
        $bin->setCacheDir( $this->binlog_cache_dir );
        $bin->setDebug( $this->debug );

        $dispatcher = new DispatchQueue( $this );

        $queue_name = self::QUEUE_NAME. ":ep";
        $queue = new Queue( $queue_name.$i, Context::instance()->redis_local);
        while( 1 )
        {
            clearstatcache();
            ob_start();

            try {
                do {
                    $self->setStatus( $process_name );
                    $self->setIsRunning();

                    $res = $queue->pop();
                    if( !$res )
                        break;

                    list( $start_pos, $end_pos ) = explode(":",$res);

                    $cache_path = $bin->getSessions( $start_pos, $end_pos );
                    unset($end_pos,$start_pos);

                    //进程调度 看看该把cache_file扔给那个进程处理
                    $target_worker = $dispatcher->get();
                    Context::instance()->redis_local->rPush( $target_worker, $cache_path );
                    unset($target_worker,$cache_path);

                    unset($cache_path);

                } while (0);

                $self->checkStopSignal();

            }catch(\Exception $e){
                var_dump($e->getMessage());
                unset($e);
            }
            $output = null;

            if( $this->debug )
                $output = ob_get_contents();
            ob_end_clean();

            if ($output && $this->debug) {
                echo $output;
                unset($output);
            }
            usleep(10000);
        }

    }
    //解析进程
    protected function parseProcess($i){

        $process_name = "php seals >> events collector - workers - ".$i;

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle( $process_name );

        $queue = new Queue(self::QUEUE_NAME.$i, Context::instance()->redis_local );

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()->reset();

        while(1) {
            ob_start();
            try {
                $this->setStatus( $process_name );
                $this->setIsRunning();

                do {
                    $len = $queue->length();
                    if ($len <= 0) {
                        unset($len);
                        break;
                    }

                    unset($len);

                    $cache_file = $queue->pop();

                    if (!$cache_file||!file_exists(	$cache_file ) || !is_file($cache_file))
                    {
                        unset($cache_file);
                        break;
                    }

                    $file = new FileFormat($cache_file,\Seals\Library\Context::instance()->activity_pdo);

                    $file->parse(function ($database_name, $table_name, $event) {
                        $this->notify->send($database_name,$table_name,[
                            "database_name" => $database_name,
                            "table_name"    => $table_name,
                            "event_data"    => $event,
                            "app_id"        => $this->app_id
                        ]);
                    });

                    unset($file);

                    unlink($cache_file);
                    unset($cache_file);

                } while (0);

                $this->checkStopSignal();

            } catch(\Exception $e){
                var_dump($e->getMessage());
                unset($e);
            }

            $content = null;
            if( $this->debug )
            {
                $content = ob_get_contents();
            }
            ob_end_clean();
            usleep(10000);

            if( $content && $this->debug ) {
                echo $content;
                unset($content);
            }
        }
    }
    //事件分配进程
    protected function eventProcess(){

        $self         = $this;
        $process_name = "php seals >> events collector - ep";

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle( $process_name );

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()->reset();

        $bin = new \Seals\Library\BinLog(
            \Seals\Library\Context::instance()->activity_pdo
        );
        $bin->setWorkers( $this->workers );
        $bin->setCacheDir( $this->binlog_cache_dir );
        $bin->setDebug( $this->debug );

        $limit = 10000;
        while( 1 )
        {
            clearstatcache();
            ob_start();

            try {
                do {
                    $self->setStatus( $process_name );
                    $self->setIsRunning();

                    //最后操作的binlog文件
                    $last_binlog         = $bin->getLastBinLog();
                    //当前使用的binlog 文件
                    $current_binlog      = $bin->getCurrentLogInfo()["File"];

                    //获取最后读取的位置
                    list($last_start_pos, $last_end_pos) = $bin->getLastPosition();

                    //binlog切换时，比如 .00001 切换为 .00002，重启mysql时会切换
                    //重置读取起始的位置
                    if ($last_binlog != $current_binlog) {
                        $bin->setLastBinLog($current_binlog);
                        $last_start_pos = $last_end_pos = 0;
                        $bin->setLastPosition($last_start_pos, $last_end_pos);
                    }

                    unset($last_binlog);

                    //得到所有的binlog事件 记住这里不允许加limit 有坑
                    $data = $bin->getEvents($current_binlog,$last_end_pos,$limit);
                    if (!$data) {
                        unset($current_binlog,$last_start_pos,$last_start_pos);
                        break;
                    }
                    unset($current_binlog,$last_start_pos,$last_start_pos);

                    $start_pos   = $data[0]["Pos"];
                    $has_session = false;

                    foreach ( $data as $row ){
                        if( $row["Event_type"] == "Xid" ) {
                            $worker     = $this->getWorker();
                            $queue      = new Queue( $worker, Context::instance()->redis_local );

                            echo "push==>",$start_pos.":".$row["End_log_pos"],"\r\n";

                            $queue->push($start_pos.":".$row["End_log_pos"]);

                            unset($queue,$worker);
                            //设置最后读取的位置
                            $bin->setLastPosition($start_pos, $row["End_log_pos"] );

                            $has_session = true;
                            $start_pos = $row["End_log_pos"];
                        }
                    }

                    //如果没有查找到一个事务 $limit x 2 直到超过 100000 行
                    if( !$has_session ){
                        $limit = 2*$limit;
                        if( $limit > 100000 )
                            $limit = 10000;
                    }else{
                        $limit = 10000;
                    }

                } while (0);
                $self->checkStopSignal();
            }catch(\Exception $e){
                var_dump($e->getMessage());
                unset($e);
            }
            $output = null;

            if( $this->debug )
                $output = ob_get_contents();
            ob_end_clean();

            if ($output && $this->debug) {
                echo $output;
                unset($output);
            }
            usleep(10000);
        }
    }

    /**
     * @启动进程 入口函数
     */
    public function start( $deamon = false){

        echo "帮助：\r\n";
        echo "启动服务：php seals server:start\r\n";
        echo "指定进程数量：php seals server:start --n 4\r\n";
        echo "4个进程以守护进程方式启动服务：php seals server:start --n 4 --d\r\n";
        echo "重启服务：php seals server:restart\r\n";
        echo "停止服务：php seals server:stop\r\n";
        echo "服务状态：php seals server:status\r\n";
        echo "\r\n";

        if( $deamon )
        {
            self::daemonize();
        }
        //启动工作进程
        for ($i = 1; $i <= $this->workers; $i++)
        {
            $process_id = pcntl_fork();
            if ($process_id == 0)
            {
                if ($deamon)
                {
                    $this->resetStd();
                }
                echo "process ",$i," is running \r\n";
                ini_set("memory_limit", $this->memory_limit);
                $this->parseProcess($i);
            }
        }
        for ($i = 1; $i <= $this->workers; $i++) {
            $process_id = pcntl_fork();
            if ($process_id == 0) {
                //调度进程
                if ($deamon) {
                    $this->resetStd();
                }
                echo "process queue dispatch ".$i." is running \r\n";
                ini_set("memory_limit", $this->memory_limit);
                $this->dispatchProcess( $i );
            }
        }

        if ($deamon) {
            $this->resetStd();
        }
        echo "process queue dispatch is running \r\n";
        ini_set("memory_limit", $this->memory_limit);
        $this->eventProcess( );

    }


}