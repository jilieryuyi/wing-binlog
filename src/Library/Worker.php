<?php namespace Wing\Library;
//use Wing\FileSystem\WFile;

/**
 * @author yuyi
 * @created 2016/9/23 8:27
 * @email 297341015@qq.com
 */

class Worker
{
	const VERSION = "2.1";

	//父进程相关配置
	private $daemon         = false;
	private $workers        = 2;
	private $with_websocket = false;
	private $with_tcp       = false;
	private $with_redis     = false;

	private static $pid     = null;
	private $normal_stop    = false;

	//子进程相关信息
	private $event_process_id   = 0;
	private $parse_processes    = [];
	private $dispatch_processes = [];
	private $processes          = [];
	private $start_time         = null;
	private $exit_times = 0; //子进程退出次数

    /**
     * @构造函数
     */
    public function __construct($params = [
    	"daemon"  => false,
		"debug"   => true,
		"workers" => 4
    ])
    {
        $this->start_time = date("Y-m-d H:i:s");
    	//默认的pid路径，即根目录 wing.pid
		self::$pid = dirname(dirname(__DIR__))."/wing.pid";
    	foreach ($params as $key => $value) {
    		$this->$key = $value;
		}

		set_error_handler([$this, "onError"]);
    	register_shutdown_function(function(){
			$log = date("Y-m-d H:i:s")."=>". $this->getProcessDisplay()."正常退出\r\n";
    		if (!$this->normal_stop) {
    			$log = date("Y-m-d H:i:s")."=>". $this->getProcessDisplay()."异常退出\r\n";
    			if (get_current_processid() == file_get_contents(self::$pid)) {
					$log = date("Y-m-d H:i:s")."=>". $this->getProcessDisplay()."父进程异常退出\r\n";
				}
			}
			if (WING_DEBUG) {
    			echo $log;
			}
            file_put_contents(HOME."/logs/error.log", $log, FILE_APPEND);

            //如果父进程异常退出 kill掉所有子进程
            if (get_current_processid() == file_get_contents(self::$pid) && !$this->normal_stop) {
            	$log = date("Y-m-d H:i:s")."=>父进程异常退出，尝试kill所有子进程".
					$this->getProcessDisplay()."\r\n";
				if (WING_DEBUG) {
					echo $log;
				}
				file_put_contents(HOME."/logs/error.log",
					$log, FILE_APPEND);
				$this->signalHandler(SIGINT);
			}
        });


    }

    protected function getProcessDisplay()
    {
        $pid = get_current_processid();
        if ($pid == $this->event_process_id) {
            return $pid."事件收集进程";
        }

        if (in_array($pid, $this->parse_processes)) {
            return $pid."parse进程";
        }

        if (in_array($pid, $this->dispatch_processes)) {
            return $pid."dispatch进程";
        }

       return $pid;

    }


    public function onError()
    {
        file_put_contents(HOME."/logs/error.log",
            date("Y-m-d H:i:s")."=>".$this->getProcessDisplay()."发生错误：".json_encode(func_get_args(), JSON_BIGINT_AS_STRING).
            "\r\n", FILE_APPEND);
        if (WING_DEBUG) {
			var_dump(func_get_args());
		}
    }

    /**
     * signal handler
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {
        $server_id = file_get_contents(self::$pid);

        switch ($signal) {
            //stop all
            case SIGINT:
				$this->normal_stop = true;
                if ($server_id == get_current_processid()) {
                    foreach ($this->processes as $id => $pid) {
                        posix_kill($pid, SIGINT);
                    }

                    $start = time();
                    $max = 1;
                    while (1) {
                        $pid = pcntl_wait($status, WNOHANG);//WUNTRACED);
                        if ($pid > 0) {

                            if ($pid == $this->event_process_id) {
                                echo $pid,"事件收集进程退出\r\n";
                            }


                            if (in_array($pid, $this->parse_processes)) {
                                echo $pid,"parse进程退出\r\n";
                            }

                            if (in_array($pid, $this->dispatch_processes)) {
                                echo $pid,"dispatch进程退出\r\n";
                            }

                            $id = array_search($pid, $this->processes);
                            unset($this->processes[$id]);

                        }

                        if (!$this->processes || count($this->processes) <= 0) {
                            break;
                        }

                        if ((time() - $start) >= $max) {
                            foreach ($this->processes as $id => $pid) {
                                posix_kill($pid, SIGINT);
                            }
                            $max++;
                        }

                        if ((time() - $start) >= 5) {
                            echo "退出进程超时\r\n";
                            break;
                        }


                    }
                    echo "父进程退出\r\n";
                }
                echo get_current_processid(),"收到退出信号退出\r\n";
                exit(0);
                break;
            //restart
            case SIGUSR1:
//                if ($server_id == get_current_processid()) {
//                    foreach ($this->processes as $id => $pid) {
//                        posix_kill($pid,SIGINT);
//                    }
//                }

//                $cache = new File(__APP_DIR__);
//                list($deamon, $workers, $debug, $clear) = $cache->get(self::RUNTIME);
//
//                $command = "php ".__APP_DIR__."/seals server:start --n ".$workers;
//                if ($deamon)
//                    $command .= ' --d';
//                if ($debug)
//                    $command .= ' --debug';
//                if ($clear)
//                    $command .= ' --clear';
//
//                //$shell = "#!/bin/bash\r\n".$command;
//                //file_put_contents(__APP_DIR__."/restart.sh", $shell);
//                $handle = popen("/bin/sh -c \"".$command."\" >>".Context::instance()->log_dir."/server_restart.log&","r");
//
//                if ($handle) {
//                    pclose($handle);
//                }

                exit(0);
                break;
			case SIGUSR2:
				//echo get_current_processid()," show status\r\n";

				if ($server_id == get_current_processid()) {
				    $str = "\r\n".'wing-binlog, version: '.self::VERSION.' auth: yuyi email: 297341015@qq.com  QQ group: 535218312'."\r\n";
                    $str .="--------------------------------------------------------------------------------------------------------------------------\r\n";
                    $str .=sprintf("%-12s%-14s%-21s%-36s%s\r\n","process_id","events_times","start_time","running_time_len","process_name");
                    $str .= "--------------------------------------------------------------------------------------------------------------------------\r\n";

                    $str .= sprintf("%-12s%-14s%-21s%-36s%s\r\n",
                        $server_id,
                        $this->exit_times,//->getEventTimes(),
                        $this->start_time,
                        timelen_format(time() - strtotime($this->start_time)),
                        "wing php >> master process"
                    );
                    file_put_contents(HOME."/logs/status.log", $str);
					foreach ($this->processes as $id => $pid) {
						posix_kill($pid, SIGUSR2);
					}
				} else {

					//子进程
					//file_put_contents(HOME."/logs/".get_current_processid()."_get_status", 1);
				    //sprintf("","进程id 事件次数 运行时间 进程名称");
                    $current_processid = get_current_processid();

                    $str = sprintf("%-12s%-14s%-21s%-36s%s\r\n",
                        $current_processid,
                        BaseWorker::$event_times,//->getEventTimes(),
                        $this->start_time,
                        timelen_format(time() - strtotime($this->start_time)),
                        BaseWorker::$process_title
                    );
                    file_put_contents(HOME."/logs/status.log", $str, FILE_APPEND);

                }

				break;
        }
    }

    public static function stopAll()
    {
        self::$pid = dirname(dirname(__DIR__))."/wing.pid";
        $server_id = file_get_contents(self::$pid);
        posix_kill($server_id, SIGINT);
    }

	public static function showStatus()
	{
		self::$pid = dirname(dirname(__DIR__))."/wing.pid";
		$server_id = file_get_contents(self::$pid);
		posix_kill($server_id, SIGUSR2);
	}

    /**
     * @启动进程 入口函数
     */
    public function start(){

//        echo "帮助：\r\n";
//        echo "启动服务：php wing start\r\n";
//        echo "指定进程数量：php wing start --n 4\r\n";
//        echo "4个进程以守护进程方式启动服务：php seals start --n 4 --d\r\n";
//        echo "重启服务：php wing restart\r\n";
//        echo "停止服务：php wing stop\r\n";
//        echo "服务状态：php wing status\r\n";
//        echo "\r\n";


        pcntl_signal(SIGINT,  [$this, 'signalHandler'], false);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
		pcntl_signal(SIGUSR2, [$this, 'signalHandler'], false);
        pcntl_signal(SIGPIPE, SIG_IGN, false);


        if ($this->daemon) {
            $this->normal_stop = true;
            enable_deamon();
        }

        $str = "\r\n".'wing-binlog, version: '.self::VERSION.' auth: yuyi email: 297341015@qq.com  QQ group: 535218312'."\r\n";
        $str .="--------------------------------------------------------------------------------------\r\n";
        $str .=sprintf("%-12s%-21s%s\r\n","process_id","start_time","process_name");
        $str .= "--------------------------------------------------------------------------------------\r\n";

        $str .= sprintf("%-12s%-21s%s\r\n",
            get_current_processid(),
            $this->start_time,
            "wing php >> master process"
        );
        echo $str;

        for ($i = 1; $i <= $this->workers; $i++) {
            $pid = (new ParseWorker($this->workers, $i))->start(
        	        $this->daemon,
                    $this->with_tcp,
                    $this->with_websocket,
                    $this->with_redis
            );

            echo sprintf("%-12s%-21s%s\r\n",
                $pid,
                $this->start_time,
                "wing php >> parse process - ".$i
            );

        	$this->parse_processes[] = $pid;
			$this->processes[] = $pid;
        }

        for ($i = 1; $i <= $this->workers; $i++) {
            $pid = (new DispatchWorker($this->workers, $i))->start($this->daemon);

            echo sprintf("%-12s%-21s%s\r\n",
                $pid,
                $this->start_time,
                "wing php >> dispatch process - ".$i
            );

            $this->dispatch_processes[] = $pid;
            $this->processes[] = $pid;
        }

		$this->event_process_id = (new EventWorker($this->workers))->start($this->daemon);
        $this->processes[] = $this->event_process_id;


        echo sprintf("%-12s%-21s%s\r\n",
            $this->event_process_id,
            $this->start_time,
            "wing php >> events collector process"
        );

        file_put_contents(self::$pid, get_current_processid());
        $process_name = "wing php >> master process";
        set_process_title($process_name);





        while (1) {
            pcntl_signal_dispatch();

            try {
                ob_start();

                $status = 0;
                $pid    = pcntl_wait($status, WNOHANG);

                if ($pid > 0) {
                    echo $pid,"进程退出\r\n";
                    $this->exit_times++;
                    do {
                        $id = array_search($pid, $this->processes);
                        unset($this->processes[$id]);

                        if ($pid == $this->event_process_id) {
                            $this->event_process_id = (new EventWorker($this->workers))->start($this->daemon);
                            $this->processes[] = $this->event_process_id;
                            break;
                        }

                        $id = array_search($pid, $this->parse_processes);
                        if ($id !== false) {
                            unset($this->parse_processes[$id]);
                            $_pid = (new ParseWorker($this->workers, $id))->start(
                                    $this->daemon,
                                    $this->with_tcp,
                                    $this->with_websocket,
                                    $this->with_redis
                            );
                            $this->parse_processes[] = $_pid;
                            $this->processes[] = $_pid;
                            break;
                        }

                        $id = array_search($pid, $this->dispatch_processes);
                        if ($id !== false) {
                            unset($this->dispatch_processes[$id]);
                            $_pid = (new DispatchWorker($this->workers, $id))->start($this->daemon);
                            $this->dispatch_processes[] = $_pid;
                            $this->processes[] = $_pid;
                            break;
                        }

                    } while(0);

                }
                $content = ob_get_contents();
                ob_end_clean();

                if (WING_DEBUG && $content) {
                	echo $content,"\r\n";
				}

            } catch (\Exception $e) {
            	if (WING_DEBUG)
				var_dump($e->getMessage());
            }
            sleep(1);
        }

        echo "服务异常退出\r\n";
    }

}