<?php namespace Wing\Library;
/**
 * Queue.php
 * User: huangxiaoan
 * Created: 2017/8/4 13:14
 * Email: huangxiaoan@xunlei.com
 */
class Queue
{
	private $index = 0;
	private $cache = null;
	public function __construct($queue_name)
	{
		$cache_dir = dirname(dirname(__DIR__))."/cache";
		if (is_dir($cache_dir)) {
			mkdir($cache_dir);
		}

		$queue_dir = $cache_dir."/queue";
		if (is_dir($queue_dir)) {
			mkdir($queue_dir);
		}

		$queue_dir =  $cache_dir."/queue/".$queue_name;
		if (is_dir($queue_dir)) {
			mkdir($queue_dir);
		}

		$this->cache = $queue_dir;
		$this->index = 0;

		$path[] = $queue_dir.'/*';

		while (count($path) != 0) {
			$v = array_shift($path);
			foreach(glob($v) as $item) {
				if (is_file($item)) {
					 $temp = explode("/", $item);
					 $this->index = array_pop($temp);
				}
			}
		}

		if ($this->index > 99999990) {
			$this->index = 0;
		}

	}

	public function push($data)
	{
		$this->index++;
		return file_put_contents($this->cache."/".$this->index, json_encode($data));
	}

	public function pop()
	{
		$path[] = $this->cache.'/*';

		while (count($path) != 0) {
			$v = array_shift($path);
			foreach(glob($v) as $item) {
				if (is_file($item)) {
					$data = file_get_contents($item);
					$data = json_decode($data, true);
					unlink($item);
					return $data;
				}
			}
		}

		return null;
	}

	public function length()
	{
		$path[] = $this->cache.'/*';

		$length = 0;
		while (count($path) != 0) {
			$v = array_shift($path);
			foreach(glob($v) as $item) {
				if (is_file($item)) {
					$length++;
				}
			}
		}

		return $length;
	}

	public function peek()
	{
		$path[] = $this->cache.'/*';

		while (count($path) != 0) {
			$v = array_shift($path);
			foreach(glob($v) as $item) {
				if (is_file($item)) {
					$data = file_get_contents($item);
					$data = json_decode($data, true);
					return $data;
				}
			}
		}

		return null;
	}
}