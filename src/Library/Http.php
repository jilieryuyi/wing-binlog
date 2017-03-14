<?php namespace Seals\Library;

use Seals\Cache\File;
use Seals\Web\MimeType;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;
use Seals\Web\Http as Server;
use Seals\Web\HttpResponse;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 20:41
 */


class Http implements Process
{
    protected $debug            = false;
    protected $start_time       = 0;
    protected $process_cache    = __APP_DIR__."/http_process_cache";
    protected $deamon           = false;
    protected $ip               = "0.0.0.0";
    protected $port             = 9998;
    protected $home_path        = __APP_DIR__."/web";
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

    /**
     * @停止进程
     *
     * @return void
     */
    public function stop()
    {

        $files = $this->process_cache->keys("running.*");

        if (!$files)
            return;

        $process_ids = [];
        foreach ($files as $file) {
            list(, $process_id) = explode("_",$file);
            $process_ids[] = $process_id;
        }

        foreach ($process_ids as $process_id) {
            $this->process_cache->set("stop_".$process_id,1,60);
        }
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

            $res[] = [
                "process_id" => sprintf("%-8d",$status["process_id"]),
                "start_time" => date("Y-m-d H:i:s", $status["start_time"]),
                "run_time"   => $time_len,
                "name"       => $status["name"]
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

        $str = "进程id    开始时间              运行时长\r\n";
        foreach ($res as $v) {
            $str .= $v["process_id"] .
                "  " . $v["start_time"] .
                "   " . $v["run_time"] .
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


    public function leaderDispatchProcess()
    {
        Context::instance()->zookeeperInit();
        Context::instance()->set("zookeeper",new Zookeeper(Context::instance()->redis_zookeeper));

        while (1) {
            $services = Zookeeper::getServices();
            foreach ($services as $group_id => $groups) {
                //获取当前群集的leader
                $leader_id = Zookeeper::getLeader($group_id);
                if ($leader_id ) {
                    //如果存在
                    $last_updated = time() - $groups[$leader_id];
                    //如果不在群集里面 或者已经超时 则删除
                    if (!isset($groups[$leader_id]) || $last_updated > 10) {
                        Zookeeper::delLeader($group_id);
                        Zookeeper::delSessionId($group_id, $leader_id);
                        $leader_id = null;
                    }
                }
                foreach ($groups as $session_id => $last_updated) {
                    if (!$leader_id) {
                        //重新设置leader
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
     * @启动进程 入口函数
     */
    public function start(){

        //设置守护进程模式
        if ($this->deamon) {
            self::daemonize();
            $this->resetStd();
        }

        $process_id = pcntl_fork();
        if ($process_id == 0) {
            //调度进程
            if ($this->deamon) {
                $this->resetStd();
            }

            $this->leaderDispatchProcess();
        }

        Context::instance()->zookeeperInit();
        Context::instance()->set("zookeeper",new Zookeeper(Context::instance()->redis_zookeeper));

        $http = new Server($this->home_path, $this->ip, $this->port);

        $http->on(Server::ON_HTTP_RECEIVE, function(HttpResponse $response) {
            $response->response();
            unset($response);
        });

        $http->start();
    }
}