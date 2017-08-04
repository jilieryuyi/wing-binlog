<?php namespace Wing\Library;
/**
 * EventWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:26
 * Email: huangxiaoan@xunlei.com
 */
class EventWorker
{
	public function __construct()
	{
	}

	public function start()
	{
		return 0;
	}

	protected function forkEventWorker()
	{
		$process_id = pcntl_fork();

		if ($process_id < 0) {
			echo "fork a process fail\r\n";
			exit;
		}

		if ($process_id > 0) {
			$this->processes[]   = $process_id;
			$this->event_process = $process_id;
			return;
		}

		if ($this->daemon) {
			$this->resetStd();
		}

		ini_set("memory_limit", Context::instance()->memory_limit);
		$process_name = "php seals >> events collector - ep";

		//设置进程标题 mac 会有warning 直接忽略
		$this->setProcessTitle($process_name);
		echo self::getCurrentProcessId()," => ", $process_name,"\r\n";

		//由于是多进程 redis和pdo等连接资源 需要重置
		Context::instance()
			->initRedisLocal()
			->initPdo()
			->zookeeperInit();
		//$generallog = new GeneralLog(Context::instance()->activity_pdo);

		$bin = new \Seals\Library\BinLog(Context::instance()->activity_pdo);
		$bin->setCacheDir(Context::instance()->binlog_cache_dir);
		$bin->setDebug($this->debug);
		$bin->setCacheHandler(new \Seals\Cache\File(__APP_DIR__));

		$zookeeper = new Zookeeper(Context::instance()->redis_zookeeper);

		$cache = new File(__APP_DIR__);
		list(, $workers, $debug, ) = $cache->get(self::RUNTIME);


		$limit = 10000;
		while (1) {
			//clearstatcache();
			ob_start();

			try {

				pcntl_signal_dispatch();
				$this->setStatus($process_name);
				$this->setIsRunning();
				$this->setInfo();

				$redis_local = Context::instance()->redis_local_config;
				unset($redis_local["password"]);

				$redis_config = Context::instance()->redis_config;
				unset($redis_config["password"]);

				$zookeeper_config = Context::instance()->zookeeper_config;
				unset($zookeeper_config["password"]);

				$db_config = Context::instance()->db_config;
				unset($db_config["password"]);

				$rabbitmq_config = Context::instance()->rabbitmq_config;
				unset($rabbitmq_config["password"]);

				for ($i = 0; $i < 10; $i++) {
					do {

						RPC::run();

						//服务发现
						$zookeeper->serviceReport([
							"is_offline"   => self::$is_offline ? 1 : 0,
							"version"      => $this->version,
							"workers"      => $workers,
							"debug"        => $debug ? 1 : 0,
							"notify"       => Context::instance()->notify_config,
							"redis_local"  => $redis_local,
							"redis_config" => $redis_config,
							"zookeeper"    => $zookeeper_config,
							"db_config"    => $db_config,
							"rabbitmq"     => $rabbitmq_config,
						]);

						//如果不是leader，需要从leader获取binlog读取状态然后同步到本地
						if (!$zookeeper->isLeader()) {
							// echo "不是leader，不进行采集操作\r\n";
							//if the current node is not leader and group is enable
							//we need to get the last pos and last binlog from leader
							//then save it to local
							$last_res = $zookeeper->getLastPost();
							if (is_array($last_res) && count($last_res) == 2) {
								if ($last_res[0] && $last_res[1])
									$bin->setLastPosition($last_res[0], $last_res[1]);
							}
							$last_binlog = $zookeeper->getLastBinlog();
							if ($last_binlog) {
								$bin->setLastBinLog($last_binlog);
							}
							break;
						}


						//最后操作的binlog文件
						$last_binlog = $bin->getLastBinLog();
						$zookeeper->setLastBinlog($last_binlog);

						//当前使用的binlog 文件
						$current_binlog = $bin->getCurrentLogInfo()["File"];

						//获取最后读取的位置
						list($last_start_pos, $last_end_pos) = $bin->getLastPosition();
						$zookeeper->setLastPost($last_start_pos, $last_end_pos);

						//binlog切换时，比如 .00001 切换为 .00002，重启mysql时会切换
						//重置读取起始的位置
						if ($last_binlog != $current_binlog) {
							$bin->setLastBinLog($current_binlog);
							$zookeeper->setLastBinlog($current_binlog);

							$last_start_pos = $last_end_pos = 0;
							$bin->setLastPosition($last_start_pos, $last_end_pos);
							$zookeeper->setLastPost($last_start_pos, $last_end_pos);
						}

						unset($last_binlog);

						//if node is offline
						if (self::$is_offline) {
							break;
						}

						//得到所有的binlog事件 记住这里不允许加limit 有坑
						$data = $bin->getEvents($current_binlog, $last_end_pos, $limit);
						if (!$data) {
							unset($current_binlog, $last_start_pos, $last_start_pos);
							break;
						}
						unset($current_binlog, $last_start_pos, $last_start_pos);

						$start_pos = $data[0]["Pos"];
						$has_session = false;

						foreach ($data as $row) {
							if ($row["Event_type"] == "Xid") {
								$queue = $this->getWorker(self::QUEUE_NAME . ":ep");

								echo "push==>", $start_pos . ":" . $row["End_log_pos"], "\r\n";

								$success = $queue->push($start_pos . ":" . $row["End_log_pos"]);
								if (!$success) {
									Context::instance()->logger->error($queue->getQueueName()." push error => ".$start_pos . ":" . $row["End_log_pos"]);
								}
								unset($queue);
								//设置最后读取的位置
								$bin->setLastPosition($start_pos, $row["End_log_pos"]);
								$zookeeper->setLastPost($start_pos, $row["End_log_pos"]);

								$has_session = true;
								$start_pos = $row["End_log_pos"];
							}
						}

						//如果没有查找到一个事务 $limit x 2 直到超过 100000 行
						if (!$has_session) {
							Context::instance()->logger->notice("没有找到事务，更新limit=".$limit);
							$limit = 2 * $limit;
							echo "没有找到事务，更新limit=", $limit, "\r\n";
							if ($limit >= 80000) {
								//如果超过8万 仍然没有找到事务的结束点 放弃采集 直接更新游标
								$row = array_pop($data);
								echo "查询超过8万，没有找到事务，直接更新游标";
								echo $start_pos, "=>", $row["End_log_pos"], "\r\n";

								Context::instance()->logger->notice("查询超过8万，没有找到事务，直接更新游标");

								$bin->setLastPosition($start_pos, $row["End_log_pos"]);
								$zookeeper->setLastPost($start_pos, $row["End_log_pos"]);

								$limit = 10000;
							}
						} else {
							$limit = 10000;
						}

					} while (0);
					usleep(self::USLEEP);
				}

				unset($redis_local, $redis_config,
					$zookeeper_config, $db_config,
					$rabbitmq_config
				);

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