<?php namespace Wing\Library;
/**
 * ParseWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:23
 * Email: huangxiaoan@xunlei.com
 */
class ParseWorker
{
	public function __construct($index)
	{
	}

	public function start()
	{
		return 0;
	}

	protected function forkParseWorker($i)
	{
		$process_id = pcntl_fork();

		if ($process_id < 0) {
			echo "fork a process fail\r\n";
			exit;
		}

		if ($process_id > 0) {
			return $process_id;
		}

		$process_name = "wing php >> events collector process - ".$i;

		//设置进程标题 mac 会有warning 直接忽略
		set_process_title($process_name);

		$queue     = new Queue("events_collector_".$i);
		$pdo       = new PDO("root", "123456", "127.0.0.1", "test", 3306);
		while (1) {
			ob_start();
			try {
				pcntl_signal_dispatch();

				do {

					$len = $queue->length();
					if ($len <= 0) {
						unset($len);
						break;
					}

					unset($len);

					$cache_file = $queue->pop();

					if (!$cache_file) {
						unset($cache_file);
						break;
					}

					if (!file_exists($cache_file)) {
						echo "cache file error => ",$cache_file,"\r\n";
						unset($cache_file);
						break;
					}

					echo "parse cache file => ",$cache_file,"\r\n";

					$file = new FileFormat($cache_file, $pdo);

					$file->parse(function ($database_name, $table_name, $event) {

						$params = [
							"database_name" => $database_name,
							"table_name"    => $table_name,
							"event_data"    => $event,
						];
						var_dump($params);
					});

					unset($file);

					echo "unlink cache file => ",$cache_file,"\r\n";

					if (file_exists($cache_file))
						unlink($cache_file);

					unset($cache_file);

				} while (0);

			} catch (\Exception $e) {
				var_dump($e->getMessage());
				unset($e);
			}

			$output = ob_get_contents();
			ob_end_clean();
			usleep(100000);

			if ($output) {
				echo $output;
			}
			unset($output);

		}

		return 0;
	}
}