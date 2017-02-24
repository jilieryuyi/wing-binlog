<?php namespace Seals\Library;
use Wing\FileSystem\WFile;

/**
 * @author yuyi
 * @created 2016/9/23 8:27
 * @email 297341015@qq.com
 * @worker worker工作调度，只负责调度
 */

class Worker implements Process{


    protected $work_dir;
    protected $log_dir;
    protected $debug            = false;
    protected $start_time       = 0;
    protected $workers          = 1;


    //队列名称
    const QUEUE_NAME = "seals:events:collector";

    /**
     * @构造函数
     */
    public function __construct()
    {
        gc_enable();

        $this->start_time = time();

        $self = $this;

        register_shutdown_function(function() use($self){
            file_put_contents(__APP_DIR__."/log/error_".date("Ymd").".log",date("Y-m-d H:i:s")."\r\n".json_encode(error_get_last(),JSON_UNESCAPED_UNICODE)."\r\n\r\n",FILE_APPEND);
            $self->clear();
        });

        set_error_handler(function($errno, $errstr, $errfile, $errline){
            file_put_contents(__APP_DIR__."/log/error_".date("Ymd").".log",date("Y-m-d H:i:s")."\r\n".json_encode(func_get_args(),JSON_UNESCAPED_UNICODE)."\r\n\r\n",FILE_APPEND);
        });

        $cpu              = new Cpu();
        $this->workers    =  $cpu->cpu_num ;

        ini_set("memory_limit","10240M");

    }

    public function __destruct()
    {
        $this->clear();
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
        $this->work_dir = $dir;
        //改变当前文件目录
        chdir( $this->work_dir );
    }

    /**
     * @设置日志目录
     */
    public function setLogDir($dir){
        $this->log_dir = $dir;
    }


    /**
     * @退出时清理一些资源
     */
    private function clear(){

        $process_id = self::getCurrentProcessId();

        $keys = [
            self::QUEUE_NAME.":is:running",
            self::QUEUE_NAME.":status",
            self::QUEUE_NAME.":is:stop"
        ];

        foreach ( $keys as $key )
        {
            Context::instance()->redis->hDel( $key, $process_id );
            $len = Context::instance()->redis->hLen( $key );
            if( $len <= 0 )
            {
                Context::instance()->redis->del( $key );
            }
        }
    }

    /**
     * @获取模块的名称
     *
     * @return string
     */
    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }


    /**
     * @设置模块正在运行
     *
     * @return self
     */
    public function setIsRunning(){
        $process_id = self::getCurrentProcessId();
        Context::instance()->redis->hSet( self::QUEUE_NAME.":is:running",$process_id,1);
        return $this;
    }

    /**
     * @获取模块是否正在运行
     *
     * @return bool
     */
    public function getIsRunning(){
        $process_id = self::getCurrentProcessId();
        return
            Context::instance()->redis->hExists( self::QUEUE_NAME.":is:running", $process_id) &&
            Context::instance()->redis->hGet( self::QUEUE_NAME.":is:running", $process_id ) == 1;
    }

    /**
     * @检查退出信号
     *
     * @return void
     */
    public function checkStopSignal(){
        $process_id = self::getCurrentProcessId();
        $is_stop = Context::instance()->redis->hExists( self::QUEUE_NAME.":is:stop",$process_id) &&
            Context::instance()->redis->hGet( self::QUEUE_NAME.":is:stop",$process_id) == 1;
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
        $process_ids = Context::instance()->redis->hKeys( self::QUEUE_NAME.":is:running" );
        if( !$process_ids )
            return;
        foreach ( $process_ids as $process_id )
        {
            Context::instance()->redis->hSet( self::QUEUE_NAME.":is:stop",$process_id,1);
        }
    }

    /**
     * @获取运行状态
     *
     * @return string
     */
    public function getStatus(){

        $arr  = Context::instance()->redis->hGetAll( self::QUEUE_NAME.":status" );
        $_res = [];

        foreach ( $arr as $process_id => $josn ){
            $t = json_decode($josn,true);
            if( (time()-$t["updated"]) >= 3 ) {
                Context::instance()->redis->hDel( self::QUEUE_NAME.":status", $process_id );
            }
            else {
                $_res[] = $t;
            }
        }

        if( count($_res) <= 0 )
            return "";

        $res = [];
        foreach ($_res as $status) {
            $time_len = (time() - $status["start_time"]);
            if ($time_len < 60)
                $time_len = $time_len . "秒";
            else if ($time_len < 3600 && $time_len >= 60) {
                $m = intval($time_len / 60);
                $s = $time_len - $m * 60;
                $time_len = $m . "分钟" . $s . "秒";
            } else if ($time_len < (24 * 3600) && $time_len >= 3600) {
                $h = intval($time_len / 3600);
                $s = $time_len - $h * 3600;
                $m = 0;
                if ($s >= 60) {
                    $m = intval($s / 60);

                } else {
                    $m = 0;
                }
                $s = $s-$m * 60;
                $time_len = $h . "小时" . $m . "分钟" . $s . "秒";
            } else {
                $d = intval($time_len / (24 * 3600));
                $s = $time_len - $d * (24 * 3600);

                $h = 0;
                $m = 0;

                if ($s < 60) {

                } else if ($s >= 60 && $s < 3600) {
                    $m = intval($s / 60);
                    $s = $s - $m * 60;
                } else {
                    $h = intval($s / 3600);
                    $s = $s - $h * 3600;
                    $m = 0;
                    if ($s >= 60) {
                        $m = intval($s / 60);
                        $s = $s - $m * 60;
                    }
                }
            }

            $res[] = [
                "process_id" => sprintf("%-8d",$status["process_id"]),
                "start_time" => date("Y-m-d H:i:s", $status["start_time"]),
                "run_time"   => $time_len,
                "name"       => $status["name"]
            ];

            $n = preg_replace("/\D/","",$status["name"]);
            if(!is_numeric($n))
                $n = 0;
            $names[] = $n+100;
        }
        array_multisort( $names, SORT_ASC, $res );

        $str = "进程id    开始时间             运行时长\r\n";
        foreach ($res as $v)
            $str.=  $v["process_id"].
                "  ". $v["start_time"].
                "  ". $v["run_time"].
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
        Context::instance()->redis->hSet(
            self::QUEUE_NAME.":status",$process_id,
            json_encode([
                "process_id" => $process_id,
                "start_time" => $this->start_time,
                "name"       => $name,
                "updated"    => time()
            ])
        );
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

        $file   = new WFile( $this->log_dir . "/seals_output_".date("Ymd").".log" );
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


    /**
     * @启动进程 入口函数
     */
    public function dispatch(  ){
        echo "start...\r\n";
        $self         = $this;
        $process_name = "php seals >> events collector - dispatch";
        echo $process_name," is running\r\n";

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle( $process_name );

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()->reset();

        $bin = new \Seals\Library\BinLog(
            \Seals\Library\Context::instance()->activity_pdo
        );

        //绑定开始执行和结束执行一个事件周期的回调函数
        $bin->setEventCallback( BinLog::EVENT_TICK_START, function() use( $self, $process_name ){
            $self->setStatus( $process_name );
            $self->setIsRunning();
        });
        $bin->setEventCallback( BinLog::EVENT_TICK_END, function() use( $self ){
            $self->checkStopSignal();
        });

        $dispatcher = new DispatchQueue( $this );

        //阻塞执行
        $bin->dispatch(function($file) use($dispatcher){
            $target_worker = $dispatcher->get();
            Context::instance()->redis->rPush( $target_worker, $file );
            unset($target_worker,$file);
        });
    }

    protected function bworker($i){


        $process_name = "php seals >> events collector - workers ".$i;
        echo $process_name," is running\r\n";

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle( $process_name );

        $queue = new Queue(self::QUEUE_NAME.$i);

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

                    $cache_file = $queue->pop();

                    if (!$cache_file||!file_exists(	$cache_file ) || !is_file($cache_file))
                    {
                        unset($cache_file);
                        break;
                    }

                    $file = new FileFormat($cache_file,\Seals\Library\Context::instance()->activity_pdo);

                    $file->parse(function ($database_name, $table_name, $event) {
                        Context::instance()->redis->rPush( "seals:event:list", json_encode([
                            "database_name" => $database_name,
                            "table_name"    => $table_name,
                            "event_data"    => $event
                        ]) );
                    });

                    unset($file);

                    unlink($cache_file);
                    unset($cache_file);

                } while (0);

                $this->checkStopSignal();

            } catch(\Exception $e){
                var_dump($e);
            }

            $content = ob_get_contents();
            ob_end_clean();
            usleep(10000);

            if( $content ) {
                echo $content;
            }
        }
    }

    public function start( $deamon = false){
        echo "start...\r\n";

        echo "\r\n";
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
                ini_set("memory_limit","10240M");
                $this->bworker($i);
            }
        }

        //调度进程
        if ($deamon)
        {
            $this->resetStd();
        }
        echo "process queue dispatch is running \r\n";
        ini_set("memory_limit","10240M");
        $this->dispatch();
    }


}