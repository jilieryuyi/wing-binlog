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
    private $task = [];
	public function __construct($workers, $index)
	{
		$this->workers = $workers;
		$this->index   = $index;
        for ($i = 1; $i <= $workers; $i++) {
            $this->task[$i] = 0;
        }
	}


	/**
	 * @return string
	 */
    private function getWorker()
    {
        $target_worker = "parse_process_1";

        if ($this->workers <= 1) {
            $this->task[1] = $this->task[1] + 1;
            if ($this->task[1] > 999999990) {
                $this->task[1] = 0;
            }
            return $target_worker;
        }

        //如果没有空闲的进程 然后判断待处理的队列长度 那个待处理的任务少 就派发给那个进程
        $target_len = $this->task[1];
        $target_index = 1;

        for ($i = 2; $i <= $this->workers; $i++) {

            if ($this->task[$i] > 999999990) {
                $this->task[$i] = 0;
            }

            $_target_worker = "parse_process_" . $i;
            $len            = $this->task[$i];

            if ($len < $target_len) {
                $target_index = $i;
                $target_worker  = $_target_worker;
                $target_len     = $len;
            }

        }
        $this->task[$target_index] = $this->task[$target_index] + 1;

        return $target_worker;
    }

    protected function scandir($callback)
    {
        $path[] = HOME."/cache/pos/dispatch_process_".$this->index.'/*';
        //$files  = [];
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_file($item)) {
                    //$files[] = $item;
                    $temp = explode("/", $item);
                    $file = array_pop($temp);
                    list($start, $end) = explode("_", $file);
                    $callback($start, $end);
                    unlink($item);
                }
            }
        }
        //return $files;
    }

	/**
	 * dispatch process
	 *
	 * @param int $i
	 */
	public function start()
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
		//$dispatch_base_queue = "dispatch_process_".$i;



		$pdo = new PDO();
		$bin = new \Wing\Library\BinLog($pdo);


		//$queue = new Queue($dispatch_base_queue);

		while (1) {
			//clearstatcache();
			ob_start();

			try {

				pcntl_signal_dispatch();


					    $this->scandir(function($start_pos, $end_pos) use($bin){
                            do {

                            if (!$end_pos)
                                break;

                             $worker =   $this->getWorker();
                            $cache_path = $bin->getSessions($worker, $start_pos, $end_pos);

                            echo "生成缓存文件",$cache_path,"\r\n";
                            //进程调度 看看该把cache_file扔给那个进程处理
//                            $target_worker = $this->getWorker();
//                            echo "cache file => ", $cache_path, "\r\n";
//                            $target_worker->push($cache_path);
//
//
//                            unset($target_worker, $cache_path);
//
//                            unset($cache_path);
                            } while (0);
                        });


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