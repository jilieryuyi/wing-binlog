<?php namespace Wing\Library;
use Wing\Subscribe\Tcp;
use Wing\Subscribe\WebSocket;

/**
 * ParseWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:23
 * Email: huangxiaoan@xunlei.com
 */
class ParseWorker extends BaseWorker
{
	private $index;
	//private $events_count = 0;
	private $file_times = 0;

	public function __construct($workers, $index)
	{
		$this->workers = $workers;
		$this->index   = $index;
	}

    protected function scandir($callback)
    {
        $path[] = HOME."/cache/binfile/parse_process_".$this->index.'/*';
        while (count($path) != 0) {
            $v = array_shift($path);
            foreach(glob($v) as $item) {
                if (is_file($item)) {
                    $t = explode("/", $item);
                    $t = array_pop($t);
                    $sub = substr($t, 0, 4);
                    if ($sub == "lock") {
                        unset($t,$sub);
                        continue;
                    }
                    unset($t,$sub);
                    $this->file_times++;
                    $callback($item);
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
            }
        }
    }

    /**
	 * @return int
	 */

	public function start($daemon = false, $with_tcp = false, $with_websocket = false, $with_redis = false)
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

		$process_name = "wing php >> parse process - ".$this->index;
        self::$process_title = $process_name;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);

		$pdo = new PDO();

        $notify = [];
		if ($with_websocket) {
            $notify[] = new WebSocket($this->workers);
		}

		if ($with_tcp) {
            $notify[] = new Tcp($this->workers);
		}

		if ($with_redis) {
            $notify[] = new \Wing\Subscribe\Redis();
        }

		while (1) {
			ob_start();
			try {
				pcntl_signal_dispatch();
                $this->scandir(function($cache_file) use($pdo, $notify){
                    do {

                        if (!$cache_file || !file_exists($cache_file)) {
                            break;
                        }

                        $file = new FileFormat($cache_file, $pdo);

                        $file->parse(function ($database_name, $table_name, $event) use($notify) {
                            $params = [
                                "database_name" => $database_name,
                                "table_name"    => $table_name,
                                "event_data"    => $event,
                            ];

							if (WING_DEBUG)
                            var_dump($params);

                            foreach ($notify as $no_item) {
                                $no_item->onchange($database_name, $table_name, $event);
							}

                           // $this->event_times++;
                            self::$event_times++;
                            $debug = get_current_processid()."处理事件次数：".self::$event_times."，文件次数：".$this->file_times."\r\n";
                            file_put_contents(HOME."/logs/parse_worker_".get_current_processid().".log", $debug);
							if (WING_DEBUG)
                            echo $debug;
                        });

                        unset($file);
                    } while (0);
                });


			} catch (\Exception $e) {
				if (WING_DEBUG)
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();
			ob_end_clean();
			usleep(100000);

			if ($output && WING_DEBUG) {
				echo $output;
			}
			unset($output);

		}

		return 0;
	}
//    public function getEventTimes()
//    {
//        // TODO: Implement getEventTimes() method.
//        return $this->event_times;
//    }


	public function process($cache_file)
	{
		if (!file_exists($cache_file)) {
			return;
		}
		$process_name = "wing php >> parse process proc_open ";
		self::$process_title = $process_name;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);

		$pdo = new PDO();


				//pcntl_signal_dispatch();
			//	$this->scandir(function($cache_file) use($pdo, $notify)
				//{
					do {

//						if (!$cache_file || !file_exists($cache_file)) {
//							break;
//						}

						$file = new FileFormat($cache_file, $pdo);

						$file->parse(function ($database_name, $table_name, $event) {
							$params = [
								"database_name" => $database_name,
								"table_name"    => $table_name,
								"event_data"    => $event,
							];

							$this->response($params);
//							if (WING_DEBUG)
//								var_dump($params);
//
//							foreach ($notify as $no_item) {
//								$no_item->onchange($database_name, $table_name, $event);
//							}

							// $this->event_times++;
							self::$event_times++;
							$debug = get_current_processid()."处理事件次数：".self::$event_times."，文件次数：".$this->file_times."\r\n";
							file_put_contents(HOME."/logs/parse_worker_".get_current_processid().".log", $debug);
							if (WING_DEBUG)
								echo $debug;
						});

						unset($file);
					} while (0);
				//}//);
	}


}