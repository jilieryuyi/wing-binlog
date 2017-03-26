<?php namespace Seals\Library;

use Seals\Cache\File;
use Seals\Web\MimeType;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;
use Seals\Web\Http;
use Seals\Web\HttpResponse;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 20:41
 */


class Master implements Process
{
    protected $debug            = false;
    protected $start_time       = 0;
    protected $process_cache    = __APP_DIR__."/http_process_cache";
    protected $deamon           = false;
    protected $ip               = "0.0.0.0";
    protected $port             = 9998;
    protected $home_path        = __APP_DIR__."/web";
    protected $master_status    = "master.status";
    protected $http_processe    = 0;
    protected $master_process   = 0;
    protected $update_process   = 0;
    protected $processes        = [];
    protected static $master_pid= __APP_DIR__."/master.pid";
    protected $version;
    /**
     * @构造函数
     */
    public function __construct(
        $ip        = "0.0.0.0",
        $port      = 9998,
        $home_path = __APP_DIR__."/web"
    ) {
        gc_enable();

        $this->start_time = time();
        $this->ip         = $ip;
        $this->port       = $port;
        $this->home_path  = $home_path;

        chdir($this->home_path);

        $dir = new WDir($this->process_cache);
        $dir->mkdir();
        unset($dir);

        $this->process_cache = new File($this->process_cache);

        register_shutdown_function(function()
        {
            $error = error_get_last();
            if ($error) {
                Context::instance()->logger->notice("http process is shutdown", [
                    "process_id" => self::getCurrentProcessId(),
                    "errors"     => $error
                ]);
            }
            $this->clear();
        });

        set_error_handler(function($errno, $errstr, $errfile, $errline)
        {
            Context::instance()->logger->error("http error happened",[
                "process_id"    => self::getCurrentProcessId(),
                "error_no"      => $errno,
                "error_message" => $errstr,
                "error_file"    => $errfile,
                "error_line"    => $errline
            ]);
        });
        $this->version = file_get_contents(__APP_DIR__."/version");
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        $this->clear();
    }

    /**
     * 启用守护进程模式
     */
    public function enableDeamon()
    {
        $this->deamon = true;
    }

    /**
     * 禁用守护进程模式
     */
    public function disableDeamon()
    {
        $this->deamon = false;
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
     * @退出时清理一些资源
     */
    private function clear()
    {
        $process_id = self::getCurrentProcessId();

        $this->process_cache->del("running_".$process_id);
        $this->process_cache->del("stop_".$process_id);
        $this->process_cache->del("status_".$process_id);
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

    public static function stopAll()
    {

        $server_id = file_get_contents(self::$master_pid);
        posix_kill($server_id, SIGINT);
    }

    /**
     * @停止进程
     *
     * @return void
     */
    public function stop()
    {
        self::stopAll();
    }

    /**
     * rpc api for restart
     *
     * @return int 1 means success
     */
    public static function restart()
    {
        posix_kill(file_get_contents(self::$master_pid), SIGUSR1);
        return 1;
    }

    public static function update()
    {
        $command = new Command("cd ".__APP_DIR__." && git pull origin master");
        $command->run();
        unset($command);
        self::restart();
        return 1;
    }

    /**
     * @获取运行状态
     *
     * @return string
     */
    public function getStatus(){

        $processes = (new File(__APP_DIR__))->get($this->master_status);

        $str = "进程id    开始时间              运行时长\r\n";
        foreach ($processes as $v) {
            $str .= $v["process_id"] .
                "  " . date("Y-m-d H:i:s",$v["created"]) .
                "   " . timelen_format(time()-$v["created"]) .
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
    public function resetStd()
    {
        if (strtolower(substr(php_uname('s'),0,3)) == "win") {
            return;
        }

        global $STDOUT, $STDERR;

        $file   = new WFile(Context::instance()->log_dir . "/seals_http_output_".date("Ymd").".log");
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
     * leader dispatch process
     */
    public function leaderDispatchProcess()
    {
        Context::instance()->zookeeperInit();
        Context::instance()->set("zookeeper",new Zookeeper(Context::instance()->redis_zookeeper));

        while (1) {
            $services = Zookeeper::getServices();
            foreach ($services as $group_id => $groups) {
                //get current group leader
                $leader_id = Zookeeper::getLeader($group_id);
                if ($leader_id) {
                    //if leader does not exists
                    $last_updated = time() - $groups[$leader_id]["updated"];
                    //if not in the group or timeout, delete it from group
                    if (!isset($groups[$leader_id]) || $last_updated > 10) {
                        Zookeeper::delLeader($group_id);
                        Zookeeper::delSessionId($group_id, $leader_id);
                        $leader_id = null;
                    }

                    //if leader is offline
                    $status = Zookeeper::getLastReport($group_id, $leader_id);
                    if ($status["is_offline"]) {
                        Zookeeper::delLeader($group_id);
                        $leader_id = null;
                    }

                }
                foreach ($groups as $session_id => $row) {

                    //if node is offline
                    $status = Zookeeper::getLastReport($group_id, $session_id);
                    if ($status["is_offline"]) {
                        continue;
                    }
                    //if group is not enable
                    if (!$leader_id) {
                        //reset a new leader
                        echo "设置leader=>",$group_id,"=>",$session_id,"\r\n";
                        Zookeeper::setLeader($group_id, $session_id);
                        break;
                    }
                }
            }
            sleep(1);
        }
    }

    /**
     * signal handler
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {
        $server_id = file_get_contents(self::$master_pid);

        switch ($signal) {
            //stop all
            case SIGINT:
                if ($server_id == self::getCurrentProcessId()) {
                    foreach ($this->processes as $id => $pid) {
                        //posix_kill($pid, SIGINT);
                        system("kill -9 ".$pid);
                    }
                }
                exit(0);
                break;
            //restart
            case SIGUSR1:
                if ($server_id == self::getCurrentProcessId()) {
                    foreach ($this->processes as $id => $pid) {
                       // posix_kill($pid,SIGINT);
                        system("kill -9 ".$pid);
                    }
                }

                $cache = new File(__APP_DIR__);
                list($deamon, $debug) = $cache->get("master.info");

                $command = "php ".__APP_DIR__."/seals master:start ";
                if ($deamon)
                    $command .= ' --d';
                if ($debug)
                    $command .= ' --debug';

                $shell = "#!/bin/bash\r\n".$command;
                file_put_contents(__APP_DIR__."/master_restart.sh", $shell);
                $handle = popen("/bin/sh ".__APP_DIR__."/master_restart.sh >>".Context::instance()->log_dir."/master_restart.log&","r");

                if ($handle) {
                    pclose($handle);
                }

                exit(0);
                break;
        }
    }


    protected function forkMasterWorker()
    {
        $processe = [];
        $process_id = pcntl_fork();
        if ($process_id == 0) {
            //调度进程
            if ($this->deamon) {
                $this->resetStd();
            }
            $this->setProcessTitle("seals >> master dispatch process");
            $this->leaderDispatchProcess();
        } else {
            $processe = [
                "process_id" => $process_id,
                "created"    => time(),
                "name"       => "seals >> master dispatch process"
            ];
            $this->master_process = $process_id;
            $this->processes[] = $process_id;
        }
        return $processe;
    }

    protected function forkCheckUpdateWorker()
    {
        $processe   = [];
        $process_id = pcntl_fork();
        if ($process_id == 0) {
            //调度进程
            if ($this->deamon) {
                $this->resetStd();
            }
            $this->setProcessTitle("seals >> check update process");

            while (1) {
                ob_start();

                do {
                    try {
                        $update_version = null;
                        if (file_exists(__APP_DIR__ . "/update")) {
                            $update_version = file_get_contents(__APP_DIR__ . "/update");
                        }

                        if ($update_version && $update_version != $this->version) {
                            //
                            break;
                        }

                        //访问网络 从github读取版本文件 然后写到 __APP_DIR__."/update"
                    } catch(\Exception $e) {

                    }

                } while(0);

                ob_end_clean();
                sleep(10);
            }

        } else {
            $processe = [
                "process_id" => $process_id,
                "created"    => time(),
                "name"       => "seals >> check update process"
            ];
            $this->update_process = $process_id;
            $this->processes[]    = $process_id;
        }
        return $processe;
    }


    protected function forkHttpWorker()
    {
        $process = null;
        $process_id = pcntl_fork();
        if ($process_id == 0) {
            if ($this->deamon) {
                $this->resetStd();
            }
            $this->setProcessTitle("seals >> master http server process");

            Context::instance()->zookeeperInit();
            Context::instance()->set("zookeeper", new Zookeeper(Context::instance()->redis_zookeeper));

            $http = new Http($this->home_path, $this->ip, $this->port);
            $http->on(Http::ON_HTTP_RECEIVE, function (HttpResponse $response) {
                $response->response();
                unset($response);
            });

            $http->start();
        } else {
            $this->master_process = $process_id;
            $this->processes[] = $process_id;
            $process = [
                "process_id" => $process_id,
                "created" => time(),
                "name" => "seals >> master http server process"
            ];
        }
        return $process;
    }

    /**
     * @启动进程 入口函数
     */
    public function start(){

        //stop
        pcntl_signal(SIGINT, [$this, 'signalHandler'], false);
        //restart
        pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
        //ignore
        pcntl_signal(SIGPIPE, SIG_IGN, false);

        //设置守护进程模式
        if ($this->deamon) {
            self::daemonize();
            $this->resetStd();
        }
        $processes = [];
        $processes[] = $this->forkMasterWorker();
        $processes[] = $this->forkHttpWorker();
        $processes[] = $this->forkCheckUpdateWorker();

        $file = new File(__APP_DIR__);
        $file->set($this->master_status, $processes);

        //write pid file
        file_put_contents(self::$master_pid, self::getCurrentProcessId());

        while (1) {
            pcntl_signal_dispatch();

            $status = 0;
            $pid    = pcntl_wait($status, WUNTRACED);

            if ($pid > 0) {

                Context::instance()->logger->notice($pid." process shutdown, try create a new process");
                $id = array_search($pid, $this->processes);
                unset($this->processes[$id]);

                if ($pid == $this->master_process) {
                    //fork master process
                    $processes[0] = $this->forkMasterWorker();
                    $file->set($this->master_status, $processes);
                    continue;
                }

                if ($pid == $this->http_processe) {
                    //fork http process
                    $processes[1] = $this->forkHttpWorker();
                    $file->set($this->master_status, $processes);
                    continue;
                }

                if ($pid == $this->update_process) {
                    //fork check update process
                    $processes[2] = $this->forkCheckUpdateWorker();
                    $file->set($this->master_status, $processes);
                    continue;
                }
            }
        }

    }
}