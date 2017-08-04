<?php namespace Wing\Library;
/**
 * DispatchWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:25
 * Email: huangxiaoan@xunlei.com
 */
class DispatchWorker
{
	public function __construct($index)
	{
	}

	public function start()
	{
		return 0;
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
			return $process_id;
		}




		$process_name = "wing php >> dispatch process - ".$i;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);




		$bin = new \Seals\Library\BinLog(Context::instance()->activity_pdo);
		$bin->setCacheDir(Context::instance()->binlog_cache_dir);
		$bin->setDebug($this->debug);
		$bin->setCacheHandler(new \Seals\Cache\File(__APP_DIR__));

		$queue = new Queue(self::QUEUE_NAME. ":ep".$i, Context::instance()->redis_local);

		while (1) {
			//clearstatcache();
			ob_start();

			try {

				pcntl_signal_dispatch();
				$this->setStatus($process_name);
				$this->setIsRunning();
				$this->setInfo();
				$this->checkCacheTimeout();

				for ($i = 0; $i < 10; $i++) {
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
						$target_worker = $this->getWorker(self::QUEUE_NAME);
						echo "cache file => ", $cache_path, "\r\n";
						$success = $target_worker->push($cache_path);

						if (!$success) {
							Context::instance()->logger->error(" redis rPush error => " . $cache_path);
						}

						unset($target_worker, $cache_path);

						unset($cache_path);

					} while (0);
					usleep(self::USLEEP);
				}

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

}