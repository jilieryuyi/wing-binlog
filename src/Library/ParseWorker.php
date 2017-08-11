<?php namespace Wing\Library;
/**
 * DispatchWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:25
 * Email: huangxiaoan@xunlei.com
 */
class ParseWorker
{
	public static function process($start_pos, $end_pos)
	{
		if (!$end_pos) {
			return null;
		}

		$pdo = new PDO();
		$bin = new \Wing\Library\BinLog($pdo);
		$raw_data = $bin->getSessions($start_pos, $end_pos);
        $file     = new FileFormat($raw_data, $pdo);
        return $file->parse();
	}

}