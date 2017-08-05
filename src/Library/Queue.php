<?php namespace Wing\Library;
/**
 * Queue.php
 * User: huangxiaoan
 * Created: 2017/8/4 13:14
 * Email: huangxiaoan@xunlei.com
 */
class Queue
{
	private $cache = null;
	private $datas = [];
	public function __construct($queue_name)
	{
		$cache_dir = dirname(dirname(__DIR__))."/cache";
		if (!is_dir($cache_dir)) {
			mkdir($cache_dir);
		}

		$queue_dir = $cache_dir."/queue";
		if (!is_dir($queue_dir)) {
			mkdir($queue_dir);
		}

		$queue_file =  $cache_dir."/queue/____queue_".$queue_name;


		$this->cache = $queue_file;

		if (file_exists($queue_file)) {
			$datas_str   = file_get_contents($queue_file);
			$this->datas = json_decode($datas_str, true);
		}

	}

	public function save()
	{
		return file_put_contents($this->cache, json_encode($this->datas));
	}

	public function push($data)
	{
		$this->datas[] = $data;
	}

	public function pop()
	{
		echo count($this->datas),"\r\n";
		return array_shift($this->datas);
	}

	public function length()
	{
		if (is_array($this->datas)) {
			return count($this->datas);
		}
		return 0;
	}

	public function peek()
	{
		if (is_array($this->datas) && count($this->datas) > 0) {
			foreach ($this->datas as $v) {
				return $v;
			}
		}
		return null;
	}
}