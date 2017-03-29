<?php namespace Seals\Library;
use Seals\Cache\File;
use Wing\FileSystem\WFile;

/**
 * @author yuyi
 * @created 2016/9/23 8:27
 * @email 297341015@qq.com
 * @property Notify $notify
 * @property CacheInterface $process_cache
 */

class Worker implements Process
{
    protected $version;
    protected $app_id             = "wing-binlog";
    protected $debug              = false;
    protected $start_time         = 0;
    protected $workers            = 1;
    protected $notify;
    protected $process_cache      = __APP_DIR__."/process_cache";
    protected $daemon             = false;
    protected static $server_pid  = __APP_DIR__."/server.pid";
    protected $processes          = [];
    protected $parse_processes    = [];
    protected $dispatch_processes = [];
    protected $event_process      = 0;
    protected $generallog_process = 0;
    //current is offline, default is false
    protected static  $is_offline    = false;
    const USLEEP = 100000;

    //队列名称
    const QUEUE_NAME = "seals:events:collector";
    const RUNTIME    = "seals.info";

    /**
     * @构造函数
     */
    public function __construct($app_id = "wing-binlog")
    {
        gc_enable();

        $this->start_time       = time();
        $this->app_id           = $app_id;

        chdir(dirname(dirname(__DIR__)));

        register_shutdown_function(function()
        {
            $error = error_get_last();
            if ($error) {
                Context::instance()->logger->notice("process is shutdown", [
                    "process_id" => self::getCurrentProcessId(),
                    "errors"     => $error
                ]);
            }
            $this->clear();
        });

        set_error_handler(function($errno, $errstr, $errfile, $errline)
        {
            Context::instance()->logger->error("error happened",[
                "process_id"    => self::getCurrentProcessId(),
                "error_no"      => $errno,
                "error_message" => $errstr,
                "error_file"    => $errfile,
                "error_line"    => $errline
            ]);
        });

        $cpu = new Cpu();
        $this->setWorkersNum($cpu->cpu_num) ;

        unset($cpu);
        ini_set("memory_limit", Context::instance()->memory_limit);
        $this->version = file_get_contents(__APP_DIR__."/version");
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->clear();
    }

    public static function getVersion()
    {
        return file_get_contents(__APP_DIR__."/version");
    }

    public static function version()
    {
        return file_get_contents(__APP_DIR__."/version");
    }

    /**
     * 事件通知方式实现
     *
     * @param Notify $notify
     */
    public function setNotify(Notify $notify)
    {
        $this->notify = $notify;
    }

    /**
     * 启用守护进程模式
     */
    public function enableDeamon()
    {
        $this->daemon = true;
    }

    /**
     * 禁用守护进程模式
     */
    public function disableDeamon()
    {
        $this->daemon = false;
    }

    /**
     * 设置进程标题，仅linux
     *
     * @param string $title 进程标题
     */
    public function setProcessTitle($title)
    {
        if (function_exists("setproctitle"))
            setproctitle($title);
        if (function_exists("cli_set_process_title"))
            cli_set_process_title($title);
    }

    /**
     * 启用debug模式
     */
    public function enabledDebug()
    {
        $this->debug = true;
        return $this;
    }

    /**
     * 禁用debug模式
     */
    public function disabledDebug()
    {
        $this->debug = false;
        return $this;
    }

    /**
     * @设置进程缓存路径
     * @param string $dir 目录路径
     */
    public function setProcessCache(CacheInterface $cache)
    {
        $this->process_cache = $cache;
    }


    /**
     * @退出时清理一些资源
     */
    private function clear()
    {
        $process_id = self::getCurrentProcessId();

        $this->process_cache->del("running_".$process_id);
        $this->process_cache->del("stop_".$process_id);
        $this->process_cache->del("status_".$process_id);
        $this->process_cache->del("restart_".$process_id);
    }

    /**
     * @获取队列名称
     *
     * @return string
     */
    public function getQueueName()
    {
        return self::QUEUE_NAME;
    }

    /**
     * @设置进程运行状态
     *
     * @return self
     */
    public function setIsRunning()
    {
        $process_id = self::getCurrentProcessId();
        $this->process_cache->set("running_".$process_id, 1, 60);
        return $this;
    }

    /**
     * @获取进程运行状态
     *
     * @return bool
     */
    public function getIsRunning()
    {
        $process_id = self::getCurrentProcessId();
        return $this->process_cache->get("running_".$process_id) == 1;
    }

    /**
     * @检查退出信号，如果检测到退出信号，则直接退出
     *
     * @return void
     */
    public function checkStopSignal()
    {
        $process_id = self::getCurrentProcessId();
        $is_stop    = $this->process_cache->get("stop_".$process_id) == 1;

        if ($is_stop) {
            echo $process_id," get stop signal\r\n";
            exit(0);
        }
    }

    public function setStopSignal($process_id)
    {
        $this->process_cache->set("stop_".$process_id,1,6);
    }

    public function stop()
    {
        self::stopAll();
    }
    /**
     * @停止进程
     *
     * @return void
     */
    public static function stopAll()
    {

        $server_id = file_get_contents(self::$server_pid);
        posix_kill($server_id, SIGINT);
    }

    /**
     * @获取运行状态
     *
     * @return string
     */
    public function getStatus(){

        $arr   = [];
        $files = $this->process_cache->keys("status.*");

        foreach ($files as $file) {
            list(, $process_id) = explode("_",$file);
            $arr[$process_id]   = $this->process_cache->get($file) ;
        }

        $res = [];
        foreach ($arr as $process_id => $status) {
            $time_len = timelen_format(time() - $status["start_time"]);
            $index    = preg_replace("/\D/","",$status["name"]);

            if (strpos($status["name"], "dispatch") !== false) {
                $queue = new Queue(self::QUEUE_NAME .":ep". $index, Context::instance()->redis_local);
                $wlen  = $queue->length();
                unset($queue);
            } else {
                $queue = new Queue(self::QUEUE_NAME . $index, Context::instance()->redis_local);
                $wlen  = $queue->length();
                unset($queue);
            }

            $res[] = [
                "process_id" => sprintf("%-8d",$status["process_id"]),
                "start_time" => date("Y-m-d H:i:s", $status["start_time"]),
                "run_time"   => $time_len,
                "name"       => $status["name"],
                "work_len"   => $wlen
            ];

            if (!is_numeric($index))
                $index = 0;

            if (strpos($status["name"],"dispatch") !== false) {
                $names[] = $index+1;
            } elseif (strpos($status["name"],"workers") !== false) {
                $names[] = $index+100;
            } else {
                $names[] = 0;
            }
        }

        array_multisort($names, SORT_ASC, $res);

        $str = "进程id    开始时间            待处理任务  运行时长\r\n";
        foreach ($res as $v) {
            $str .= $v["process_id"] .
                "  " . $v["start_time"] .
                "  " . sprintf("%-6d", $v["work_len"]) .
                "       " . $v["run_time"] .
                "  " . $v["name"] . "\r\n";
        }
        return $str;
    }

    /**
     * 设置运行状态
     *
     * @return void
     */
    public function setStatus($name)
    {
        $process_id = self::getCurrentProcessId();
        $this->process_cache->set("status_".$process_id, [
            "process_id" => $process_id,
            "start_time" => $this->start_time,
            "name"       => $name,
            "updated"    => time()
        ],60);
    }

    /**
     * @重置标准错误和标准输出，linux支持
     *
     * @param string $output_name  可选参数，主要为了区分不同进程的输出
     * @throws \Exception
     */
    protected function resetStd()
    {
        if (strtolower(substr(php_uname('s'),0,3)) == "win") {
            return;
        }

        global $STDOUT, $STDERR;

        $file   = new WFile(Context::instance()->log_dir . "/seals_output_".date("Ymd")."_".self::getCurrentProcessId().".log");
        $file->touch();

        $handle = fopen($file->get(), "a+");
        if ($handle) {
            unset($handle);
            @fclose(STDOUT);
            @fclose(STDERR);
            $STDOUT = fopen($file->get(), "a+");
            $STDERR = fopen($file->get(), "a+");
        } else {
            throw new \Exception('can not open stdout file ' . $file->get());
        }
    }

    /**
     * @服务状态
     */
    public function status()
    {
        return $this->getStatus();
    }

    /**
     * @获取进程数量
     */
    public function getWorkersNum()
    {
        return $this->workers;
    }

    /**
     * @设置进程数量
     * @param int $workers
     */
    public function setWorkersNum($workers)
    {
        $this->workers = $workers;
    }

    /**
     * @是否还在运行
     */
    public function isRunning()
    {
        return $this->getIsRunning();
    }

    /**
     * @获取当前进程id 仅linux
     *
     * @return int
     */
    public static function  getCurrentProcessId()
    {
        if (function_exists("getmypid"))
            return getmypid();

        if (function_exists("posix_getpid"))
            return posix_getpid();
        return 0;
    }

    /**
     * @守护进程化，需要安装pcntl扩展，linux支持
     */
    public static function daemonize()
    {
        if (!function_exists("pcntl_fork"))
            return;

        //修改掩码
        umask(0);

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
    }

    /**
     * 简单的进程调度实现，获取该分配的进程队列名称
     *
     * @param string $base_queue_name 基础队列名称
     * @return Queue
     */
    public function getWorker($base_queue_name)
    {
        $target_worker = new Queue($base_queue_name."1", Context::instance()->redis_local);

        if ($this->workers <= 1) {
            return $target_worker;
        }

        //如果没有空闲的进程 然后判断待处理的队列长度 那个待处理的任务少 就派发给那个进程
        $target_len    = $target_worker->length();

        for ($i = 2; $i <= $this->workers; $i++) {

            $_target_worker = new Queue($base_queue_name . $i, Context::instance()->redis_local);
            $len            = $_target_worker->length();

            if ($len < $target_len) {
                $target_worker = $_target_worker;
                $target_len    = $len;
            }

        }
        return $target_worker;
    }

    /**
     * try to resend fail events
     */
    protected function failureEventsSendRetry()
    {
        $failure_queue = new Queue(self::QUEUE_NAME.":failure:events", Context::instance()->redis_local);
        $len = $failure_queue->length();
        if ($len <= 0)
            return;
        echo "正在尝试重新发送失败的事件，请确保接收事件的服务可用...\r\n";

        for($i = 0; $i < $len; $i++) {
            $event   = $failure_queue->peek();
            $success = $this->notify->send($event);
            if (!$success) {
                echo "重新尝试失败\r\n";
                Context::instance()->logger->error("failure events send fail", $event);
            } else {
                $failure_queue->pop();
            }
        }
        echo "失败事件重新发送完毕\r\n";
    }


    /**
     * rpc api for restart
     *
     * @return int 1 means success
     */
    public static function restart()
    {
        $pid = file_get_contents(self::$server_pid);
        posix_kill($pid, SIGUSR1);
        $file = new File(__APP_DIR__."/process_cache");
        $file->set("restart_".$pid,1,6);

        return 1;
    }

    /**
     * rpc api, set current node offline or online
     */
    public static function setNodeOffline($is_offline = false)
    {
        self::$is_offline = !!$is_offline;
        return 1;
    }

    /**
     * rpc api for update system
     *
     * @return int 1 means success
     */
    public static function update()
    {
        $command = new Command("cd ".__APP_DIR__." && git pull origin master");
        $command->run();
        unset($command);
        self::restart();
        return 1;
    }

    /**
     * signal handler
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {
        $server_id = file_get_contents(self::$server_pid);

        switch ($signal) {
            //stop all
            case SIGINT:
                if ($server_id == self::getCurrentProcessId()) {
                    foreach ($this->processes as $id => $pid) {
                        $this->setStopSignal($pid);
                        posix_kill($pid, SIGINT);
                    }
                }
                exit(0);
                break;
            //restart
            case SIGUSR1:
                if ($server_id == self::getCurrentProcessId()) {
                    foreach ($this->processes as $id => $pid) {
                        $this->setStopSignal($pid);
                        posix_kill($pid,SIGINT);
                    }
                }

                $cache = new File(__APP_DIR__);
                list($deamon, $workers, $debug, $clear) = $cache->get(self::RUNTIME);

                $command = "php ".__APP_DIR__."/seals server:start --n ".$workers;
                if ($deamon)
                    $command .= ' --d';
                if ($debug)
                    $command .= ' --debug';
                if ($clear)
                    $command .= ' --clear';

                //$shell = "#!/bin/bash\r\n".$command;
                //file_put_contents(__APP_DIR__."/restart.sh", $shell);
                $handle = popen("/bin/sh -c \"".$command."\" >>".Context::instance()->log_dir."/server_restart.log&","r");

                if ($handle) {
                    pclose($handle);
                }

                exit(0);
                break;
        }
    }

    /**
     * 解析进程
     * @param int $i
     */
    protected function forkParseWorker($i)
    {
        $process_id = pcntl_fork();

        if ($process_id < 0) {
            echo "fork a process fail\r\n";
            exit;
        }

        if ($process_id > 0) {
            $this->processes[]         = $process_id;
            $this->parse_processes[$i] = $process_id;
            return;
        }


        if ($this->daemon) {
            $this->resetStd();
        }

        echo "process queue dispatch ".$i." is running \r\n";
        ini_set("memory_limit", Context::instance()->memory_limit);

        $process_name = "php seals >> events collector - workers - ".$i;

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle($process_name);

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()
            ->initRedisLocal()
            ->initPdo();

        $queue         = new Queue(self::QUEUE_NAME.$i, Context::instance()->redis_local);
        $failure_queue = new Queue(self::QUEUE_NAME.":failure:events", Context::instance()->redis_local);
        $filter        = Context::instance()->getAppConfig("filter");
        $report        = new Report(Context::instance()->redis_local);


        while (1) {
            ob_start();
            try {
                pcntl_signal_dispatch();
                $this->setStatus($process_name);
                $this->setIsRunning();

                do {

                    //最后一个进程做失败重发操作
                    if (!$i == $this->workers) {
                        //尝试发送失败的事件
                        $this->failureEventsSendRetry();
                    }

                    $len = $queue->length();
                    if ($len <= 0) {
                        unset($len);
                        break;
                    }

                    unset($len);

                    $cache_file = $queue->pop();

                    if (!$cache_file) {
                        unset($cache_file);
                        break;
                    }

                    if (!file_exists($cache_file) || !is_file($cache_file)) {
                        echo "cache file error => ",$cache_file,"\r\n";
                        echo "queue => ",$queue->getQueueName(),"\r\n";
                        $queue_all  = $queue->getAll();

                        $error_info = "redis pop error => ". $cache_file ."\r\n".$this->getQueueName()."\r\n";

                        if (is_array($queue_all))
                            $error_info .= json_encode($queue_all,JSON_UNESCAPED_UNICODE);
                        else
                            $error_info .= $queue_all;

                        Context::instance()->logger->error($error_info);

                        unset($cache_file);
                        break;
                    }

                    echo "parse cache file => ",$cache_file,"\r\n";
                    $file = new FileFormat($cache_file,\Seals\Library\Context::instance()->activity_pdo);

                    $file->parse(function ($database_name, $table_name, $event) use($failure_queue, $filter, $report) {

                        $continue = false;
                        //过滤器支持
                        if (is_array($filter) && count($filter) > 0) {
                            foreach ($filter as $_database_name => $tables ) {
                                if ($_database_name != $database_name)
                                    continue;
                                /*if (strpos($_database_name,"/") !== false ) {
                                    $p = str_replace("/","",$_database_name);
                                    if (preg_match("/".$p."/", $database_name)) {
                                        $continue = true;
                                        break;
                                    }
                                } else {
                                    if($database_name == $_database_name) {
                                        $continue = true;
                                        break;
                                    }
                                }*/

                                foreach ($tables as $table) {
                                    if (strpos($table, "/") !== false ) {
                                        $p = str_replace("/", "", $table);
                                        if (preg_match("/".$p."/", $table_name)) {
                                            $continue = true;
                                            break;
                                        }
                                    } else {
                                        if($table_name == $table) {
                                            $continue = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }

                        if ($continue) {
                            echo $database_name,"=>",$table_name,"已设置为忽略\r\n";
                            return;
                        }

                        $params = [
                            "database_name" => $database_name,
                            "table_name"    => $table_name,
                            "event_data"    => $event,
                            "app_id"        => $this->app_id
                        ];
                        $success = $this->notify->send($params);
                        $report->eventsIncr($event["time"], $event["event_type"]);
                        if (!$success) {
                            Context::instance()->logger->error(get_class($this->notify)." send failure",$params);
                            $failure_queue->push($params);
                        }
                    });

                    unset($file);

                    echo "unlink cache file => ",$cache_file,"\r\n";
                    $success = unlink($cache_file);

                    if (!$success) {
                        echo "unlink failure \r\n";
                        Context::instance()->logger->error("unlink failure => ".$cache_file);
                    } else {
                        echo "unlink success \r\n";
                    }
                    unset($cache_file);

                } while (0);

                $this->checkStopSignal();

            } catch (\Exception $e) {
                Context::instance()->logger->error($e->getMessage());
                var_dump($e->getMessage());
                unset($e);
            }

            $output = ob_get_contents();
            Context::instance()->logger->info($output);
            ob_end_clean();
            usleep(self::USLEEP);

            if ($output && $this->debug) {
                echo $output;
            }
            unset($output);

        }

    }

    /**
     * dispatch process
     *
     * @param int $i
     */
    protected function forkDispatchWorker($i)
    {
        $process_id = pcntl_fork();

        if ($process_id < 0) {
            echo "fork a process fail\r\n";
            exit;
        }

        if ($process_id > 0) {
            $this->processes[]            = $process_id;
            $this->dispatch_processes[$i] = $process_id;
            return;
        }

        if ($this->daemon) {
            $this->resetStd();
        }

        ini_set("memory_limit", Context::instance()->memory_limit);

        $process_name = "php seals >> events collector - dispatch - ".$i;

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle($process_name);

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()
            ->initRedisLocal()
            ->initPdo();


        $bin = new \Seals\Library\BinLog(Context::instance()->activity_pdo);
        $bin->setCacheDir(Context::instance()->binlog_cache_dir);
        $bin->setDebug($this->debug);
        $bin->setCacheHandler(new \Seals\Cache\File(__APP_DIR__));

        $queue = new Queue(self::QUEUE_NAME. ":ep".$i, Context::instance()->redis_local);

        while (1) {
            clearstatcache();
            ob_start();

            try {
                do {
                    pcntl_signal_dispatch();
                    $this->setStatus($process_name);
                    $this->setIsRunning();

                    $res = $queue->pop();
                    if (!$res)
                        break;

                    echo "pos => ",$res,"\r\n";
                    list($start_pos, $end_pos) = explode(":",$res);

                    if (!$start_pos || !$end_pos)
                        break;

                    $cache_path = $bin->getSessions($start_pos, $end_pos);
                    unset($end_pos,$start_pos);

                    //进程调度 看看该把cache_file扔给那个进程处理
                    $target_worker = $this->getWorker(self::QUEUE_NAME);
                    echo "cache file => ",$cache_path,"\r\n";
                    $success = $target_worker->push($cache_path);

                    if (!$success) {
                        Context::instance()->logger->error(" redis rPush error => ".$cache_path);
                    }

                    unset($target_worker,$cache_path);

                    unset($cache_path);

                } while (0);

                $this->checkStopSignal();

            } catch (\Exception $e) {
                Context::instance()->logger->error($e->getMessage());
                var_dump($e->getMessage());
                unset($e);
            }

            $output = ob_get_contents();
            Context::instance()->logger->info($output);
            ob_end_clean();

            if ($output && $this->debug) {
                echo $output;
            }
            unset($output);
            usleep(self::USLEEP);
        }
    }


    /**
     * base generallog process
     */
    protected function forkGenerallogWorker()
    {
        echo "general log start...\r\n";
        $process_id = pcntl_fork();

        if ($process_id < 0) {
            echo "fork a process fail\r\n";
            exit;
        }

        if ($process_id > 0) {
            $this->processes[] = $process_id;
            $this->generallog_process = $process_id;
            return;
        }

        if ($this->daemon) {
            $this->resetStd();
        }

        ini_set("memory_limit", Context::instance()->memory_limit);
        $process_name = "php seals >> events collector - general log";

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle($process_name);

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()
            ->initRedisLocal()
            ->initPdo()
            ->zookeeperInit();

        $report  = new Report(Context::instance()->redis_local);
        $general = new GeneralLog(Context::instance()->activity_pdo);
        $type    = $general->logOutput();

        $count      = 0;
        $start_time = time();

        if ($type == "table") {
            while (1) {
                ob_start();
                try {
                    pcntl_signal_dispatch();
                    $log_output = $general->logOutput();
                    if ($log_output != "table") {
                        echo "切换格式 from table to file\r\n";
                        exit;
                    }
                    unset($log_output);

                    do {
                        $generallog_is_open = $general->isOpen();
                        if (!$generallog_is_open) {
                            unset($generallog_is_open);
                            echo "general log is disable\r\n";
                            sleep(1);
                            break;
                        }
                        unset($generallog_is_open);

                        $data = $general->query($general->last_time);
                        if (!$data) {
                            usleep(self::USLEEP);
                            break;
                        }

                        foreach ($data as $row) {
                            list($event,) = explode(" ", $row["argument"]);
                            $event = strtolower($event);
                            echo $row["argument"],"\r\n";

                            $count++;
                            if ((time() - $start_time) > 0)
                            echo "采集量：", $count, ",每秒采集:", ($count / (time() - $start_time)), "条\r\n";

                            $report->set(strtotime($row["event_time"]), strtolower($event));
                            echo date("Y-m-d H:i:s", strtotime($row["event_time"])),"=>",strtolower($row["command_type"]),"=>",strtolower($event),"\r\n";
                            unset($event);
                        }
                        unset($data);

                    } while(0);

                    $this->checkStopSignal();
                } catch (\Exception $e) {

                }
                $content = null;
                if ($this->debug)
                    $content = ob_get_contents();
                ob_end_clean();

                if ($this->debug && $content)
                    echo $content;
                unset($content);
            }
        }

        elseif ($type == "file") {

            $file_name = $general->getLogPath();
            $read_size = $general->getReadSize();

            clearstatcache();

            if ($read_size > filesize($file_name)) {
                //reset read size
                $read_size = 0;
            }


            $fp = fopen($file_name, "r");

            if ($fp)
                fseek($fp, $read_size);
            else
                $fp = null;

            while (1) {
                try {
                    ob_start();
                    pcntl_signal_dispatch();
                    $log_output = $general->logOutput();
                    if ($log_output != "file") {
                        echo "切换格式 from file to table\r\n";
                        fclose($fp);
                        $fp = null;
                        //after exit the current process will create a new one
                        exit;
                    }

                    unset($log_output);

                    do {
                        $general_is_open = $general->isOpen();
                        if (!$general_is_open) {
                            echo "general log disable\r\n";
                            if ($fp)
                                fclose($fp);
                            $fp = null;
                            unset($general_is_open);
                            sleep(1);
                            break;
                        }
                        unset($general_is_open);

                        if ($fp == null) {
                            clearstatcache();
                            $fp = fopen($file_name, "r");
                            fseek($fp, $read_size);
                        }
                        //read 10000 lines then check 1 isOpen and logOutput
                        for ($i = 0; $i < 1000; ++$i)
                        {
                            $line  = fgets($fp);
                            $lsize = strlen($line);

                            $read_size += $lsize;
                            unset($lsize);

                            $general->setReadSize($read_size);

                            $_line    = trim($line);
                            unset($line);

                            $temp     = preg_split("/[\s]+/", $_line, 4);
                            unset($_line);
                            $datetime = strtotime($temp[0]);

                            if ($datetime <= 0)
                                continue;

                            var_dump($temp);
                            $event_type = trim($temp[2]);
                            if ($event_type == "Init")
                                $event_type = "Init DB";
                            elseif ($event_type == "Close")
                                $event_type = "Close stmt";

                            $event = "";
                            if (isset($temp[3]) && $temp[3] != "stmt") {
                                list($event,) = explode(" ", $temp[3], 2);
                                $event = strtolower($event);
                            }
                            unset($temp);

                            $report->set($datetime, $event);
                            echo date("Y-m-d H:i:s", $datetime), "=>", strtolower($event_type), "=>", $event, "\r\n";
                            unset($datetime, $event_type, $event);
                            $count++;
                            if ((time() - $start_time) > 0)
                            echo "采集量：", $count, ",每秒采集:", ($count / (time() - $start_time)), "条\r\n";
                            if (feof($fp)) {
                                fclose($fp);
                                $fp = null;
                                usleep(self::USLEEP);


                                //if read end
                                $_file_name = $general->getLogPath();
                                //if file path is change
                                if ($_file_name != $file_name) {
                                    echo "chang general log file path\r\n";
                                    $file_name = $_file_name;
                                    $fp        = fopen($file_name, "r");
                                    $read_size = 0;
                                    fseek($fp, $read_size);
                                }
                                unset($_file_name);

                                break;
                            }
                        }

                    } while(0);

                    $this->checkStopSignal();

                    $content = null;
                    if ($this->debug)
                        $content = ob_get_contents();
                    ob_end_clean();

                    if ($this->debug && $content)
                    echo $content;

                    unset($content);
                } catch (\Exception $e) {
                    Context::instance()->logger->error($e->getMessage());
                    fclose($fp);
                    $fp = null;
                    usleep(self::USLEEP);

                    $file_name = $general->getLogPath();
                    $read_size = $general->getReadSize();

                    clearstatcache();

                    if ($read_size > filesize($file_name)) {
                        //reset read size
                        $read_size = 0;
                    }

                    //after error happened, try to open again
                    $fp = fopen($file_name, "r");
                    fseek($fp, $read_size);
                }
            }
            if ($fp) {
                fclose($fp);
            }

        }

    }

    /**
     * base events collector、rpc process
     */
    protected function forkEventWorker()
    {
        $process_id = pcntl_fork();

        if ($process_id < 0) {
            echo "fork a process fail\r\n";
            exit;
        }

        if ($process_id > 0) {
            $this->processes[]   = $process_id;
            $this->event_process = $process_id;
            return;
        }

        if ($this->daemon) {
            $this->resetStd();
        }

        ini_set("memory_limit", Context::instance()->memory_limit);
        $process_name = "php seals >> events collector - ep";

        //设置进程标题 mac 会有warning 直接忽略
        $this->setProcessTitle($process_name);

        //由于是多进程 redis和pdo等连接资源 需要重置
        Context::instance()
            ->initRedisLocal()
            ->initPdo()
            ->zookeeperInit();
        $generallog = new GeneralLog(Context::instance()->activity_pdo);

        $bin = new \Seals\Library\BinLog(Context::instance()->activity_pdo);
        $bin->setCacheDir(Context::instance()->binlog_cache_dir);
        $bin->setDebug($this->debug);
        $bin->setCacheHandler(new \Seals\Cache\File(__APP_DIR__));

        $zookeeper = new Zookeeper(Context::instance()->redis_zookeeper);

        $cache = new File(__APP_DIR__);
        list(, $workers, $debug, ) = $cache->get(self::RUNTIME);


        $limit = 10000;
        while (1) {
            clearstatcache();
            ob_start();

            try {
                do {
                    pcntl_signal_dispatch();
                    $this->setStatus($process_name);
                    $this->setIsRunning();

                    $redis_local = Context::instance()->redis_local_config;
                    unset($redis_local["password"]);

                    $redis_config = Context::instance()->redis_config;
                    unset($redis_config["password"]);

                    $zookeeper_config = Context::instance()->zookeeper_config;
                    unset($zookeeper_config["password"]);

                    $db_config = Context::instance()->db_config;
                    unset($db_config["password"]);

                    $rabbitmq_config = Context::instance()->rabbitmq_config;
                    unset($rabbitmq_config["password"]);

                    RPC::run();

                    //服务发现
                    $zookeeper->serviceReport([
                        "is_offline"   => self::$is_offline?1:0,
                        "version"      => $this->version,
                        "workers"      => $workers,
                        "debug"        => $debug ? 1 : 0,
                        "notify"       => Context::instance()->notify_config,
                        "redis_local"  => $redis_local,
                        "redis_config" => $redis_config,
                        "zookeeper"    => $zookeeper_config,
                        "db_config"    => $db_config,
                        "rabbitmq"     => $rabbitmq_config,
                        "generallog"   => $generallog->isOpen()?1:0
                    ]);
                    unset($redis_local, $redis_config,
                        $zookeeper_config, $db_config, $rabbitmq_config);

                    if (!$zookeeper->isLeader()) {
                        // echo "不是leader，不进行采集操作\r\n";
                        //if the current node is not leader and group is enable
                        //we need to get the last pos and last binlog from leader
                        //then save it to local
                        $last_res = $zookeeper->getLastPost();
                        if (is_array($last_res) && count($last_res) == 2) {
                            if ($last_res[0] && $last_res[1])
                                $bin->setLastPosition($last_res[0], $last_res[1]);
                        }
                        $last_binlog = $zookeeper->getLastBinlog();
                        if ($last_binlog) {
                            $bin->setLastBinLog($last_binlog);
                        }
                        break;
                    }


                    //最后操作的binlog文件
                    $last_binlog         = $bin->getLastBinLog();
                    $zookeeper->setLastBinlog($last_binlog);

                    // echo "是leader\r\n";



                    //当前使用的binlog 文件
                    $current_binlog      = $bin->getCurrentLogInfo()["File"];

                    //获取最后读取的位置
                    list($last_start_pos, $last_end_pos) = $bin->getLastPosition();
                    $zookeeper->setLastPost($last_start_pos, $last_end_pos);

                    //binlog切换时，比如 .00001 切换为 .00002，重启mysql时会切换
                    //重置读取起始的位置
                    if ($last_binlog != $current_binlog) {
                        $bin->setLastBinLog($current_binlog);
                        $zookeeper->setLastBinlog($current_binlog);

                        $last_start_pos = $last_end_pos = 0;
                        $bin->setLastPosition($last_start_pos, $last_end_pos);
                        $zookeeper->setLastPost($last_start_pos, $last_end_pos);
                    }

                    unset($last_binlog);

                    //if node is offline
                    if (self::$is_offline) {
                        break;
                    }

                    //得到所有的binlog事件 记住这里不允许加limit 有坑
                    $data = $bin->getEvents($current_binlog,$last_end_pos,$limit);
                    if (!$data) {
                        unset($current_binlog,$last_start_pos,$last_start_pos);
                        break;
                    }
                    unset($current_binlog,$last_start_pos,$last_start_pos);

                    $start_pos   = $data[0]["Pos"];
                    $has_session = false;

                    foreach ($data as $row){
                        if ($row["Event_type"] == "Xid") {
                            $queue = $this->getWorker(self::QUEUE_NAME. ":ep");

                            echo "push==>",$start_pos.":".$row["End_log_pos"],"\r\n";

                            $queue->push($start_pos.":".$row["End_log_pos"]);

                            unset($queue);
                            //设置最后读取的位置
                            $bin->setLastPosition($start_pos, $row["End_log_pos"]);
                            $zookeeper->setLastPost($start_pos, $row["End_log_pos"]);

                            $has_session = true;
                            $start_pos = $row["End_log_pos"];
                        }
                    }

                    //如果没有查找到一个事务 $limit x 2 直到超过 100000 行
                    if (!$has_session) {
                        $limit = 2*$limit;
                        echo "没有找到事务，更新limit=",$limit,"\r\n";
                        if ($limit >= 80000) {
                            //如果超过8万 仍然没有找到事务的结束点 放弃采集 直接更新游标
                            $row = array_pop($data);
                            echo "查询超过8万，没有找到事务，直接更新游标";
                            echo $start_pos, "=>", $row["End_log_pos"],"\r\n";

                            $bin->setLastPosition($start_pos, $row["End_log_pos"]);
                            $zookeeper->setLastPost($start_pos, $row["End_log_pos"]);

                            $limit = 10000;
                        }
                    } else {
                        $limit = 10000;
                    }

                } while (0);
                $this->checkStopSignal();
            } catch (\Exception $e) {
                Context::instance()->logger->error($e->getMessage());
                var_dump($e->getMessage());
                unset($e);
            }

            $output = ob_get_contents();
            Context::instance()->logger->info($output);
            ob_end_clean();

            if ($output && $this->debug) {
                echo $output;
            }
            unset($output);
            usleep(self::USLEEP);
        }

    }


    protected function checkRestart()
    {
        echo "check restart\r\n";
        $restart = $this->process_cache->get("restart_".self::getCurrentProcessId()) == 1;
        if ($restart) {
            $this->signalHandler(SIGUSR1);
        }
    }

    /**
     * @启动进程 入口函数
     */
    public function start(){

        echo "帮助：\r\n";
        echo "启动服务：php seals server:start\r\n";
        echo "指定进程数量：php seals server:start --n 4\r\n";
        echo "4个进程以守护进程方式启动服务：php seals server:start --n 4 --d\r\n";
        echo "重启服务：php seals server:restart\r\n";
        echo "停止服务：php seals server:stop\r\n";
        echo "服务状态：php seals server:status\r\n";
        echo "\r\n";


        //stop
        pcntl_signal(SIGINT, [$this, 'signalHandler'], false);
        //restart
        pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
        //ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);

        //set daemon mode
        if ($this->daemon) {
            self::daemonize();
        }

        //fork workers process
        for ($i = 1; $i <= $this->workers; $i++) {
            $this->forkParseWorker($i);
            $this->forkDispatchWorker($i);
        }

        //try resend fail events
        $this->failureEventsSendRetry();
        $this->forkGenerallogWorker();
        $this->forkEventWorker();

        //write pid file
        file_put_contents(self::$server_pid, self::getCurrentProcessId());
        $this->setProcessTitle("php seals >> events master process - Worker");
        while (1) {
            pcntl_signal_dispatch();

            try {
                ob_start();
                $this->checkRestart();
                $status = 0;
                $pid = pcntl_wait($status, WNOHANG);//WUNTRACED);

                if ($pid > 0) {

                    Context::instance()->logger->notice($pid . " process shutdown, try create a new process");
                    $id = array_search($pid, $this->processes);
                    unset($this->processes[$id]);

                    if ($pid == $this->event_process) {
                        $this->forkEventWorker();
                        continue;
                    }

                    $id = array_search($pid, $this->parse_processes);
                    if ($id !== false) {
                        unset($this->parse_processes[$id]);
                        $this->forkParseWorker($id);
                        continue;
                    }

                    $id = array_search($pid, $this->dispatch_processes);
                    if ($id !== false) {
                        unset($this->dispatch_processes[$id]);
                        $this->forkDispatchWorker($id);
                        continue;
                    }

                    if ($pid == $this->generallog_process) {
                        $this->forkGenerallogWorker();
                        continue;
                    }
                }
                //$content = ob_get_contents();
                ob_end_clean();
                //echo $content;
                //unset($content);
            } catch (\Exception $e) {
                Context::instance()->logger->error($e->getMessage());
            }
            sleep(1);
        }

        echo "service shutdown\r\n";
    }

}