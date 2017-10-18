<?php namespace Wing\Library;

use Wing\Library\Workers\BaseWorker;
use Wing\Library\Workers\BinlogWorker;

/**
 * 服务进程，处理整体的进程控制和指令控制支持
 *
 * @author yuyi
 * @created 2016/9/23 8:27
 * @email 297341015@qq.com
 */

class Worker
{
	const VERSION = "2.2.0";

	//父进程相关配置
	private $daemon         = false;
	private $workers        = 2;
	private static $pid     = null;
	private $normal_stop    = false;

	//子进程相关信息
	private $event_process_id   = 0;
	private $processes          = [];
	private $start_time         = null;
	private $exit_times         = 0; //子进程退出次数
	private $worker				= BinlogWorker::class;

    /**
     * 构造函数
	 *
	 * @param array $params 进程参数
     */
    public function __construct($params = [
    	"daemon"  => false,
		"debug"   => true,
		"workers" => 4
    ])
    {
        $this->start_time = date("Y-m-d H:i:s");
    	//默认的pid路径，即根目录 wing.pid
		self::$pid = HOME."/wing.pid";
    	foreach ($params as $key => $value) {
    		$this->$key = $value;
		}

		set_error_handler([$this, "onError"]);
    	register_shutdown_function(function()
		{
			$log   = date("Y-m-d H:i:s")."=>". $this->getProcessDisplay()."正常退出";
    		$winfo = self::getWorkerProcessInfo();

    		if (!$this->normal_stop) {
    			$log =  $this->getProcessDisplay()."异常退出";
    			if (get_current_processid() == $winfo["process_id"]) {
					$log = $this->getProcessDisplay()."父进程异常退出";
				}
				$log .= json_encode(error_get_last() , JSON_UNESCAPED_UNICODE);
			}

			if (WING_DEBUG) {
    			wing_debug($log);
			}

			wing_log("error", $log);

            //如果父进程异常退出 kill掉所有子进程
            if (get_current_processid() == $winfo["process_id"] && !$this->normal_stop) {
            	$log = "父进程异常退出，尝试kill所有子进程". $this->getProcessDisplay();

            	if (WING_DEBUG) {
					wing_debug($log);
				}

				$log .= json_encode(error_get_last() , JSON_UNESCAPED_UNICODE);
				wing_log("error", $log);

				$this->signalHandler(SIGINT);
			}
        });
    }

	/**
	 * 获取进程运行时信息
	 *
	 * @return array
	 */
    public static function getWorkerProcessInfo()
    {
        self::$pid = HOME."/wing.pid";
        $data = file_get_contents(self::$pid);
        list($pid, $daemon, $debug, $workers) = json_decode($data, true);

        return [
            "process_id" => $pid,
            "daemon"     => $daemon,
            "debug"      => $debug,
            "workers"    => $workers
        ];
    }

    /**
     * 获取进程的展示名称
	 *
	 * @return string
	 */
    protected function getProcessDisplay()
    {
        $pid = get_current_processid();
        if ($pid == $this->event_process_id) {
            return $pid."事件收集进程";
        }

        return $pid;
    }

	/**
	 * 错误回调函数
	 */
    public function onError()
    {
        wing_log("error", $this->getProcessDisplay()."发生错误：", func_get_args());
        if (WING_DEBUG) {
			wing_debug(func_get_args());
		}
    }

    /**
     * signal handler 信号回调函数
     *
     * @param int $signal
     */
    public function signalHandler($signal)
    {
        $winfo     = self::getWorkerProcessInfo();
        $server_id = $winfo["process_id"];

        switch ($signal) {
            //stop all
            case SIGINT:
				$this->normal_stop = true;
                if ($server_id == get_current_processid()) {
                    foreach ($this->processes as $id => $pid) {
                        posix_kill($pid, SIGINT);
                    }

                    $start = time();
                    $max   = 1;

                    while (1) {
                        $pid = pcntl_wait($status, WNOHANG);//WUNTRACED);
                        if ($pid > 0) {
                            if ($pid == $this->event_process_id) {
                                wing_debug($pid,"事件收集进程退出");
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
                            wing_debug("退出进程超时");
                            break;
                        }
                    }
                    wing_debug("父进程退出");
                }
                wing_debug(get_current_processid(),"收到退出信号退出");
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
				    $str  = "\r\n".'wing-binlog, version: ' . self::VERSION . ' auth: yuyi email: 297341015@qq.com  QQ group: 535218312'."\r\n";
                    $str .= "--------------------------------------------------------------------------------------------------------------------------\r\n";
                    $str .= sprintf("%-12s%-14s%-21s%-36s%s\r\n",
                        "process_id","events_times","start_time",
                        "running_time_len","process_name");
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
                    $str               = sprintf("%-12s%-14s%-21s%-36s%s\r\n",
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

    /**
     * 停止所有的进程
	 */
    public static function stopAll()
    {
        $winfo     = self::getWorkerProcessInfo();
        $server_id = $winfo["process_id"];
        posix_kill($server_id, SIGINT);
    }

    /**
     * 展示所有的进程状态信息
	 */
	public static function showStatus()
	{
        $winfo     = self::getWorkerProcessInfo();
        $server_id = $winfo["process_id"];
		posix_kill($server_id, SIGUSR2);
	}

    /**
     * @启动进程 入口函数
     */
    public function start()
	{
        pcntl_signal(SIGINT,  [$this, 'signalHandler'], false);
        pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
		pcntl_signal(SIGUSR2, [$this, 'signalHandler'], false);
        pcntl_signal(SIGPIPE, SIG_IGN, false);

        if ($this->daemon) {
            $this->normal_stop = true;
            enable_deamon();
        }

        $format = "%-12s%-21s%s\r\n";
        $str    = "\r\n".'wing-binlog, version: ' .self::VERSION. ' auth: yuyi email: 297341015@qq.com  QQ group: 535218312'."\r\n";
        $str   .="--------------------------------------------------------------------------------------\r\n";
        $str   .=sprintf($format,"process_id","start_time","process_name");
        $str   .= "--------------------------------------------------------------------------------------\r\n";

        $str   .= sprintf(
					$format,
					get_current_processid(),
					$this->start_time,
					"wing php >> master process"
				);

        echo $str;
        unset($str, $format);

		/**
		 * @var BaseWorker $worker
		 */
        $worker = new $this->worker($this->daemon, $this->workers);
		$this->event_process_id = $worker->start();
		unset($worker);
        $this->processes[] = $this->event_process_id;


        echo sprintf("%-12s%-21s%s\r\n",
            $this->event_process_id,
            $this->start_time,
            "wing php >> events collector process"
        );

        file_put_contents(self::$pid, json_encode([
                get_current_processid(),
                $this->daemon,
                WING_DEBUG,
                $this->workers
            ])
        );
        set_process_title("wing php >> master process");

        while (1) {
            pcntl_signal_dispatch();

            try {
                ob_start();

                $status = 0;
                $pid    = pcntl_wait($status, WNOHANG);

                if ($pid > 0) {
                    wing_debug($pid,"进程退出");
                    $this->exit_times++;
                    do {
                        $id = array_search($pid, $this->processes);
                        unset($this->processes[$id]);

                        if ($pid == $this->event_process_id) {
                            $worker = new $this->worker($this->daemon, $this->workers);
                            $this->event_process_id = $worker->start();
                            unset($worker);
                            $this->processes[] = $this->event_process_id;
                            break;
                        }

                    } while(0);

                }
                $content = ob_get_contents();
                ob_end_clean();

                if (WING_DEBUG && $content) {
                	wing_debug($content);
				}

            } catch (\Exception $e) {
            	if (WING_DEBUG)
				var_dump($e->getMessage());
            }
            sleep(1);
        }

        wing_debug("master服务异常退出");
    }

}