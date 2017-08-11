<?php namespace Wing\Library;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;

/**
 * EventWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:26
 * Email: huangxiaoan@xunlei.com
 */
class EventWorker extends BaseWorker
{
	private $all_pos = [];
	private $dispatch_pipes = [];
	private $dispatch_processes = [];
	private $parse_pipes = [];

	public function __construct($workers)
	{
		$this->workers = $workers;
		for ($i = 1; $i <= $this->workers; $i++) {
		    $this->task[$i] = 0;
        }
	}


	private function startDisplatchProcess()
	{
		list($start_pos, $end_pos) = array_shift($this->all_pos);
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);
		$cmd = "php " . HOME . "/dispatch_worker --start=".$start_pos." --end=".$end_pos;
		$this->dispatch_processes[] = proc_open($cmd, $descriptorspec, $pipes);
		$this->dispatch_pipes[]     = $pipes[1];
		//$all_pipes[] = $pipes[2];
		stream_set_blocking($pipes[1], 0);
		//stream_set_blocking($pipes[2], 0);  //不阻塞
		//fwrite($pipes[0], "");
		fclose($pipes[0]);
		fclose($pipes[2]); //标准错误直接关闭 不需要
		return true;
	}


	private function waitDispatchProcess()
	{

	//	do {
			$read = $this->dispatch_pipes;//array($pipes[1],$pipes[2]);
			$write = null;
			$except = null;
			$timeleft = 60;//$endtime - time();
			$ret = stream_select(
				$read,
				$write,// = null,
				$except,// = null,
				$timeleft
			);

			if ($ret === false) {
				return false;
			} else if ($ret === 0) {
				$timeleft = 0;
				return false;
			} else {
				//var_dump($ret);
				foreach ($read as $sock) {
					//if ($sock === $pipes[1]) {
					$cache_file =  fread($sock, 10240);//, "\r\n";

					if (file_exists($cache_file)) {
						//进行解析进程
					}

					$id = array_search($sock, $this->dispatch_pipes);
					unset($this->dispatch_pipes[$id]);
					proc_close($this->dispatch_processes[$id]);
					unset($this->dispatch_processes[$id]);
//				} else if ($sock === $pipes[2]) {
//					echo  fread($sock, 4096),"\r\n";
//				}
					fclose($sock);
				}
			}
		//}while(count($all_pipes) > 0 && $timeleft > 0 );

//	fclose($pipes[1]);
//	fclose($pipes[2]);
//		if($timeleft <= 0) {
//			foreach ($processes as $process)
//				proc_terminate($process);
//			$stderr_str = "操作已超时(".$timeout."秒)";
//		}
//
//		if (isset($err) && $err === true){  //这种情况的出现是通过信号发送中断时产生
//			foreach ($processes as $process)
//				proc_terminate($process);
//			$stderr_str = "操作被用户取消";
//		}
//		foreach ($processes as $process)
//			proc_close($process);
		return true;
	}


	private function writePos($worker, $start_pos, $end_pos)
    {
    	$this->all_pos[] = [$start_pos, $end_pos];

    	if (count($this->dispatch_pipes) < $this->workers) {
    		$count = $this->workers - count($this->dispatch_pipes);
    		//启动 $count 个 dispatch 进程
			for ($i = 0; $i < $count; $i++) {
				$this->startDisplatchProcess();
			}
		}

		$this->waitDispatchProcess();

//        self::$event_times++;
//        $debug = get_current_processid()."写入pos的次数：".$this->event_times."\r\n";
//        file_put_contents(HOME."/logs/event_worker.log", $debug);
//
//        if (WING_DEBUG)
//        	echo $debug;
//
//        $dir_str = HOME."/cache/pos/".$worker;
//        $dir = new WDir($dir_str);
//        $dir->mkdir();
//
//        $file = new WFile($dir_str."/".$start_pos."_".$end_pos);
//        return $file->touch();


		//启动 $this->workers 个进程去处理
//		$processes = [];
//		$all_pipes = [];
//
//		for ($i = 0; $i < 1; $i++) {
//			$descriptorspec = array(
//				0 => array("pipe", "r"),
//				1 => array("pipe", "w"),
//				2 => array("pipe", "w")
//			);
//			$cmd = "php " . HOME . "/dispatch_worker --start=".$start_pos." --end=".$end_pos;
//			$processes[$i] = proc_open($cmd, $descriptorspec, $pipes);
//			$all_pipes[]   = $pipes[1];
//			//$all_pipes[] = $pipes[2];
//			stream_set_blocking($pipes[1], 0);
//			//stream_set_blocking($pipes[2], 0);  //不阻塞
//			fwrite($pipes[0], $i);
//			fclose($pipes[0]);
//			fclose($pipes[2]); //标准错误直接关闭 不需要
//
//		}
//$stdout_str = $stderr_str = $stdin_str ="";
		//$timeout = 60;



//if (is_resource($process))
		//{
			//执行成功


			//设置超时时钟
		//	$endtime = time() + $timeout;

			//return true;
		//}
//else {
//	return false;
//}

    }

//    public function getEventTimes()
//    {
//        return $this->event_times;
//    }

	public function start($daemon = false)
	{
		$process_id = pcntl_fork();

		if ($process_id < 0) {
			echo "fork a process fail\r\n";
			exit;
		}

		if ($process_id > 0) {
			return $process_id;
		}


		if ($daemon) {
		    reset_std();
        }

		$process_name = "wing php >> events collector process";
		self::$process_title = $process_name;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);


		$pdo             = new PDO();
		$bin             = new \Wing\Library\BinLog($pdo);
		$limit           = 10000;
        $last_binlog     = null;
        $current_binlog  = null;
        $last_start_pos  =
        $last_end_pos    = 0;
        $run_count       = 0;
        $is_run          = intval(1000000/self::USLEEP);

		while (1) {
			ob_start();

			try {

				pcntl_signal_dispatch();
                do {
                    $run_count++;
                    //最后操作的binlog文件
                    if (null == $last_binlog || $run_count % $is_run == 0) {
                        $last_binlog = $bin->getLastBinLog();
                    }

                    if (null == $current_binlog || $run_count % $is_run == 0) {
                        //当前使用的binlog 文件
                        $current_binlog = $bin->getCurrentLogInfo()["File"];
                    }

                    if ($last_start_pos == 0 && $last_end_pos == 0) {
                        //获取最后读取的位置
                        list($last_start_pos, $last_end_pos) = $bin->getLastPosition();
                    }

                    //binlog切换时，比如 .00001 切换为 .00002，重启mysql时会切换
                    //重置读取起始的位置
                    if ($last_binlog != $current_binlog) {
                        $bin->setLastBinLog($current_binlog);
                        $last_start_pos =
                        $last_end_pos = 0;
                        $bin->setLastPosition($last_start_pos, $last_end_pos);
                    }

                    //得到所有的binlog事件
                    $data = $bin->getEvents($current_binlog, $last_end_pos, $limit);

                    if (!$data) {
                        break;
                    }

                    $start_pos   = $data[0]["Pos"];
                    $has_session = false;

                    foreach ($data as $row) {
                        if ($row["Event_type"] == "Xid") {
                            $worker = $this->getWorker("dispatch_process");
							if (WING_DEBUG)
                            echo "写入pos位置：", $start_pos . "-" . $row["End_log_pos"], "\r\n";
                            $res = $this->writePos($worker, $start_pos, $row["End_log_pos"]);
                            if (!$res && WING_DEBUG) {
                                echo "失败\r\n";
                            }
//                            if ($run_count % $is_run == 0) {
//                                //设置最后读取的位置
//                                $bin->setLastPosition($start_pos, $row["End_log_pos"]);
//                            }
                            $last_start_pos = $start_pos;
                            $last_end_pos   = $row["End_log_pos"];

                            $has_session    = true;
                            $start_pos      = $row["End_log_pos"];
                        }
                    }

                        $bin->setLastPosition($last_start_pos, $last_end_pos);
                        //如果没有查找到一个事务 $limit x 2 直到超过 100000 行
						if (!$has_session) {
							$limit = 2 * $limit;
							if (WING_DEBUG)
							echo "没有找到事务，更新limit=", $limit, "\r\n";
							if ($limit >= 80000) {
								//如果超过8万 仍然没有找到事务的结束点 放弃采集 直接更新游标
								$row = array_pop($data);
								if (WING_DEBUG) {
									echo "查询超过8万，没有找到事务，直接更新游标";
									echo $start_pos, "=>", $row["End_log_pos"], "\r\n";
								}

								$bin->setLastPosition($start_pos, $row["End_log_pos"]);

								$last_start_pos = $start_pos;
                                $last_end_pos   = $row["End_log_pos"];
								$limit          = 10000;
							}
						} else {
							$limit = 10000;
						}
                        if ($run_count%$is_run == 0) {
						    $run_count = 0;
                        }
					} while (0);

			} catch (\Exception $e) {
				if (WING_DEBUG)
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();

			ob_end_clean();

			if ($output && WING_DEBUG) {
				echo $output,"\r\n";
			}
			unset($output);
			usleep(self::USLEEP);
		}

		return 0;
	}
}