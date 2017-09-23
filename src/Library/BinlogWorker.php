<?php namespace Wing\Library;

/**
 * EventWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:26
 * Email: huangxiaoan@xunlei.com
 */
class BinlogWorker extends BaseWorker
{
	private $all_pos            = [];
	private $dispatch_pipes     = [];
	private $dispatch_processes = [];

	private $write_run_time     = 0;
	//private $pdo;

	private $notify = [];
	private $daemon;
    private $event_index        = 0;

    /**
     * @var \Wing\Bin\Binlog
	 */
	private $binlog;
    public function __construct($daemon, $workers)
	{
		$config = load_config("app");
		//认证
		\Wing\Bin\Auth\Auth::execute(
			$config["mysql"]["host"],
			$config["mysql"]["user"],
			$config["mysql"]["password"],
			$config["mysql"]["db_name"],
			$config["mysql"]["port"]
		);

		$this->binlog = new BinLog(new PDO);

		//注册为slave
		$this->binlog->registerSlave(
			null,
			0,
			!!\Wing\Bin\Db::getChecksum(),
			$config["slave_server_id"]
		);

		if ($config
            && isset($config["subscribe"])
            && is_array($config["subscribe"])
            && count($config["subscribe"]) > 0) {
		    foreach ($config["subscribe"] as $class => $params) {
                $params["daemon"]  = $daemon;
                $params["workers"] = $workers;
                $this->notify[] = new $class($params);
            }
        }
	}

	public function start()
	{
        $daemon = $this->daemon;

        if (!is_env(WINDOWS)) {
			$process_id = pcntl_fork();

			if ($process_id < 0) {
				wing_debug("创建子进程失败");
				exit;
			}

			if ($process_id > 0) {
				return $process_id;
			}


			if ($daemon) {
				reset_std();
			}
		}

		$process_name = "wing php >> events collector process";
		self::$process_title = $process_name;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);

		$times = 0;
		$start = time();

		while (1) {
			ob_start();

			try {
				pcntl_signal_dispatch();

				$result = $this->binlog->getEvent();
				if ($result) {
					$times += count($result["event"]["data"]);
					$s = time()-$start;
					if ($s > 0) {
						echo $times,"次，",$times/($s)."/次事件每秒，耗时",$s,"秒\r\n";
					}

					//通知订阅者
					if (is_array($this->notify) && count($this->notify) > 0) {
						$datas = $result["event"]["data"];
						foreach ($datas as $row) {
							$result["event"]["data"] = $row;
							var_dump($result);
							foreach ($this->notify as $notify) {
								$notify->onchange($result);
							}
						}
					}
				}
			} catch (\Exception $e) {
				if (WING_DEBUG)
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();

			ob_end_clean();

			if ($output && WING_DEBUG) {
				wing_debug($output);
			}
			unset($output);
			usleep(self::USLEEP);
		}

		return 0;
	}
}