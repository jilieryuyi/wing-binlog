<?php namespace Wing\Library;
/**
 * DispatchWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:25
 * Email: huangxiaoan@xunlei.com
 */
class DispatchWorker extends BaseWorker
{
	private $index;

    public function __construct($workers, $index)
	{
		$this->workers = $workers;
		$this->index   = $index;
        for ($i = 1; $i <= $workers; $i++) {
            $this->task[$i] = 0;
        }
	}

    protected function scandir($callback)
    {
        $path[] = HOME."/cache/pos/dispatch_process_".$this->index.'/*';
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_file($item)) {
                    $temp = explode("/", $item);
                    $file = array_pop($temp);
                    list($start, $end) = explode("_", $file);

                    if (WING_DEBUG) {
						echo "正在处理文件：", $item, "--";
						echo $start, ":", $end, "\r\n";
					}

                    $callback($start, $end);
                    unlink($item);

                    if (file_exists($item)) {
                        unlink($item);
                        if (file_exists($item)) {
                            unlink($item);
                            if (file_exists($item)) {
                                unlink($item);
                                if (file_exists($item)) {
                                    unlink($item);
                                }
                            }
                        }
                    }
                }
                else {
                    usleep(self::USLEEP);
                }
                usleep(100);
            }
            usleep(100);
        }
    }

	/**
	 * dispatch process
	 */
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

		$process_name = "wing php >> dispatch process - ".$this->index;
        self::$process_title = $process_name;

		//echo $process_name,"\r\n";
		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);


		$pdo = new PDO();
		$bin = new \Wing\Library\BinLog($pdo);

		while (1) {
			//clearstatcache();
			ob_start();

			try {

				pcntl_signal_dispatch();
                $this->scandir(function($start_pos, $end_pos) use($bin){
                    do {
                        if (!$end_pos) {
                            //echo "非法endpos\r\n";
                            break;
                        }

                        $worker     = $this->getWorker("parse_process");
                        $cache_path = $bin->getSessions($worker, $start_pos, $end_pos);
						if (WING_DEBUG)
                        echo "生成缓存文件",$cache_path,"\r\n";
                        self::$event_times++;
						//$this->event_times++;
//                        if (!file_exists($cache_path)) {
//                            echo "文件不存在\r\n";
//                        } else {
                          //  $this->event_times++;
                           // file_put_contents(HOME."/logs/dispatch_worker".get_current_processid().".log", $this->event_times);
                        //}

                    } while (0);
                });

			} catch (\Exception $e) {
				if (WING_DEBUG)
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();
			ob_end_clean();

			if ($output && WING_DEBUG ) {
				echo $output, "\r\n";
			}
			unset($output);
			usleep(self::USLEEP * 10);
		}
		return 0;
	}

	public function process($start_pos, $end_pos)
	{

		if (!$end_pos) {
			$this->response("");
			return;
		}
		$process_name = "wing php >> dispatch process by proc_open";
		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);

		$pdo = new PDO();
		$bin = new \Wing\Library\BinLog($pdo);

		//$worker     = $this->getWorker("parse_process");
		$cache_path = $bin->getSessions($start_pos, $end_pos);

		$this->response($cache_path);
	}

}