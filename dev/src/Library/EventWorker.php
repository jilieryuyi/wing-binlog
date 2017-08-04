<?php namespace Wing\Library;
use Wing\FileSystem\WDir;
use Wing\FileSystem\WFile;

/**
 * EventWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:26
 * Email: huangxiaoan@xunlei.com
 */
class EventWorker
{
	private $workers = 1;
	const USLEEP = 100000;

	private $task = [];

	public function __construct($workers)
	{
		$this->workers = $workers;
		for ($i = 1; $i <= $workers; $i++) {
		    $this->task[$i] = 0;
        }
	}

	/**
	 * @return string
	 */
	private function getWorker()
	{
		$target_worker = "dispatch_process_1";

		if ($this->workers <= 1) {
		    $this->task[1] = $this->task[1] + 1;
		    if ($this->task[1] > 999999990) {
                $this->task[1] = 0;
            }
			return $target_worker;
		}

		//如果没有空闲的进程 然后判断待处理的队列长度 那个待处理的任务少 就派发给那个进程
		$target_len   = $this->task[1];
		$target_index = 1;

		for ($i = 2; $i <= $this->workers; $i++) {

            if ($this->task[$i] > 999999990) {
                $this->task[$i] = 0;
            }

		    $_target_worker = "dispatch_process_" . $i;
			$len            = $this->task[$i];

			if ($len < $target_len) {
				$target_worker  = $_target_worker;
				$target_len     = $len;
                $target_index   = $i;
			}

		}

        $this->task[$target_index] = $this->task[$target_index] + 1;

        return $target_worker;
	}

	private function writePos($worker, $start_pos, $end_pos)
    {
        $dir_str = HOME."/cache/pos/".$worker;
        $dir = new WDir($dir_str);
        $dir->mkdir();

        $file = new WFile($dir_str."/".$start_pos."_".$end_pos);
        $file->touch();
    }

	public function start()
	{
		$process_id = pcntl_fork();

		if ($process_id < 0) {
			echo "fork a process fail\r\n";
			exit;
		}

		if ($process_id > 0) {
			return $process_id;
		}


		$process_name = "wing php >> events base collector";

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

                    $start_pos = $data[0]["Pos"];
                    $has_session = false;

                    foreach ($data as $row) {
                        if ($row["Event_type"] == "Xid") {
                            $worker = $this->getWorker();
                            echo "push==>", $start_pos . ":" . $row["End_log_pos"], "\r\n";
                            //$queue->push([$start_pos, $row["End_log_pos"]]);
                            $this->writePos($worker, $start_pos, $row["End_log_pos"]);

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
							echo "没有找到事务，更新limit=", $limit, "\r\n";
							if ($limit >= 80000) {
								//如果超过8万 仍然没有找到事务的结束点 放弃采集 直接更新游标
								$row = array_pop($data);
								echo "查询超过8万，没有找到事务，直接更新游标";
								echo $start_pos, "=>", $row["End_log_pos"], "\r\n";

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
                    usleep(self::USLEEP);

			} catch (\Exception $e) {
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();

			ob_end_clean();

			if ($output) {
				echo $output,"\r\n";
			}
			unset($output);
			usleep(self::USLEEP);
		}

		return 0;
	}
}