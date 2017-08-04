<?php namespace Wing\Library;
/**
 * DispatchWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:25
 * Email: huangxiaoan@xunlei.com
 */
class DispatchWorker
{
	private $workers = 1;
	private $index;
	public function __construct($workers, $index)
	{
		$this->workers = $workers;
		$this->index = $index;
	}


	/**
	 * @return Queue
	 */
	public function start()
	{
		$target_worker = new Queue("parse_process_1");

		if ($this->workers <= 1) {
			return $target_worker;
		}

		//如果没有空闲的进程 然后判断待处理的队列长度 那个待处理的任务少 就派发给那个进程
		$target_len = $target_worker->length();

		for ($i = 2; $i <= $this->workers; $i++) {
			$_target_worker = new Queue("parse_process_" . $i);
			$len            = $_target_worker->length();

			if ($len < $target_len) {
				$target_worker = $_target_worker;
				$target_len    = $len;
			}

		}
		return $target_worker;
	}

	/**
	 * dispatch process
	 *
	 * @param int $i
	 */
	protected function forkDispatchWorker()
	{
		$i = $this->index;
		$process_id = pcntl_fork();

		if ($process_id < 0) {
			echo "fork a process fail\r\n";
			exit;
		}

		if ($process_id > 0) {
			return $process_id;
		}




		$process_name = "wing php >> dispatch process - ".$i;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);
		$dispatch_base_queue = "dispatch_process_".$i;



		$pdo = new PDO();
		$bin = new \Wing\Library\BinLog($pdo);


		$queue = new Queue($dispatch_base_queue);

		while (1) {
			//clearstatcache();
			ob_start();

			try {

				pcntl_signal_dispatch();



					do {
						$res = $queue->pop();
						if (!$res)
							break;

						echo "pos => ", $res, "\r\n";
						list($start_pos, $end_pos) = explode(":", $res);

						if (!$start_pos || !$end_pos)
							break;

						$cache_path = $bin->getSessions($start_pos, $end_pos);
						unset($end_pos, $start_pos);

						//进程调度 看看该把cache_file扔给那个进程处理
						$target_worker = $this->getWorker();
						echo "cache file => ", $cache_path, "\r\n";
						$target_worker->push($cache_path);


						unset($target_worker, $cache_path);

						unset($cache_path);

					} while (0);
					usleep(100000);


				//$this->checkStopSignal();

			} catch (\Exception $e) {
				//Context::instance()->logger->error($e->getMessage());
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();
			//Context::instance()->logger->info($output);
			ob_end_clean();

			if ($output) {
				echo $output, "\r\n";
			}
			unset($output);
			usleep(100000);
		}
		return 0;
	}

}