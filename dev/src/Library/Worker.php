<?php namespace Wing\Library;

/**
 * @author yuyi
 * @created 2016/9/23 8:27
 * @email 297341015@qq.com
 */

class Worker
{
	const VERSION = "2.0";

	//父进程相关配置
	private $daemon  = false;
	private $debug   = true;
	private $workers = 2;
	private $pid     = null;

	//子进程相关信息
	private $event_process_id   = 0;
	private $parse_processes    = [];
	private $dispatch_processes = [];
	private $processes          = [];
    /**
     * @构造函数
     */
    public function __construct($params = [
    	"daemon"  => false,
		"debug"   => true,
		"workers" => 4
    ])
    {
    	//默认的pid路径，即根目录 wing.pid
		$this->pid = dirname(dirname(__DIR__))."/wing.pid";
    	foreach ($params as $key => $value) {
    		$this->$key = $value;
		}
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {

    }

    /**
     * signal handler
     *
     * @param int $signal
     */
//    public function signalHandler($signal)
//    {
//        $server_id = file_get_contents($this->pid);
//
//        switch ($signal) {
//            //stop all
//            case SIGINT:
//                if ($server_id == get_current_processid()) {
//                    foreach ($this->processes as $id => $pid) {
//                        posix_kill($pid, SIGINT);
//                    }
//                }
//                exit(0);
//                break;
//            //restart
//            case SIGUSR1:
////                if ($server_id == get_current_processid()) {
////                    foreach ($this->processes as $id => $pid) {
////                        posix_kill($pid,SIGINT);
////                    }
////                }
//
////                $cache = new File(__APP_DIR__);
////                list($deamon, $workers, $debug, $clear) = $cache->get(self::RUNTIME);
////
////                $command = "php ".__APP_DIR__."/seals server:start --n ".$workers;
////                if ($deamon)
////                    $command .= ' --d';
////                if ($debug)
////                    $command .= ' --debug';
////                if ($clear)
////                    $command .= ' --clear';
////
////                //$shell = "#!/bin/bash\r\n".$command;
////                //file_put_contents(__APP_DIR__."/restart.sh", $shell);
////                $handle = popen("/bin/sh -c \"".$command."\" >>".Context::instance()->log_dir."/server_restart.log&","r");
////
////                if ($handle) {
////                    pclose($handle);
////                }
//
//                exit(0);
//                break;
//        }
//    }

    /**
     * @启动进程 入口函数
     */
    public function start(){

        echo "帮助：\r\n";
        echo "启动服务：php wing server:start\r\n";
        echo "指定进程数量：php wing server:start --n 4\r\n";
        echo "4个进程以守护进程方式启动服务：php seals server:start --n 4 --d\r\n";
        echo "重启服务：php wing server:restart\r\n";
        echo "停止服务：php wing server:stop\r\n";
        echo "服务状态：php wing server:status\r\n";
        echo "\r\n";


//        pcntl_signal(SIGINT,  [$this, 'signalHandler'], false);
//        pcntl_signal(SIGUSR1, [$this, 'signalHandler'], false);
//        pcntl_signal(SIGPIPE, SIG_IGN, false);


        if ($this->daemon) {
            enable_deamon();
        }

        for ($i = 1; $i <= $this->workers; $i++) {
        	$pid = (new ParseWorker($this->workers, $i))->start();
			$this->parse_processes[] = $pid;
			$this->processes[] = $pid;

			$pid = (new DispatchWorker($this->workers, $i))->start();
			$this->dispatch_processes[] = $pid;
			$this->processes[] = $pid;
        }

		$this->event_process_id = (new EventWorker($this->workers))->start();
		$this->processes[] = $this->event_process_id;

        file_put_contents($this->pid, get_current_processid());
        $process_name = "wing php => master process";
        set_process_title($process_name);

        while (1) {
            pcntl_signal_dispatch();

            try {
                ob_start();

                $status = 0;
                $pid    = pcntl_wait($status, WNOHANG);//WUNTRACED);

                if ($pid > 0) {

					$id = array_search($pid, $this->processes);
					unset($this->processes[$id]);


                    if ($pid == $this->event_process_id) {
						$this->event_process_id = (new EventWorker($this->workers))->start();
						$this->processes[] = $this->event_process_id;
                        continue;
                    }

                    $id = array_search($pid, $this->parse_processes);
                    if ($id !== false) {
                        unset($this->parse_processes[$id]);
						$_pid = (new ParseWorker($this->workers, $i))->start();
						$this->parse_processes[] = $_pid;
						$this->processes[] = $_pid;
                        continue;
                    }

                    $id = array_search($pid, $this->dispatch_processes);
                    if ($id !== false) {
                        unset($this->dispatch_processes[$id]);
						$_pid = (new DispatchWorker($this->workers, $i))->start();
						$this->dispatch_processes[] = $_pid;
						$this->processes[] = $_pid;
                        continue;
                    }

                }
                $content = ob_get_contents();
                ob_end_clean();

                if ($this->debug && $content) {
                	echo $content,"\r\n";
				}

            } catch (\Exception $e) {
				var_dump($e->getMessage());
            }
            sleep(1);
        }

        echo "服务异常退出\r\n";
    }

}