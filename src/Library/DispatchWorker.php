<?php namespace Wing\Library;
/**
 * DispatchWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:25
 * Email: huangxiaoan@xunlei.com
 */
class DispatchWorker
{
	public static function process($start_pos, $end_pos)
	{
		if (!$end_pos) {
			return null;
		}

		$pdo = new PDO();
		$bin = new \Wing\Library\BinLog($pdo);
		return $bin->getSessions($start_pos, $end_pos);
	}

}