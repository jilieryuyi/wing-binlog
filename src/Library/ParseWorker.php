<?php namespace Wing\Library;
use Wing\Subscribe\Tcp;
use Wing\Subscribe\WebSocket;

/**
 * ParseWorker.php
 * User: huangxiaoan
 * Created: 2017/8/4 12:23
 * Email: huangxiaoan@xunlei.com
 */
class ParseWorker
{

	public static function process($cache_file)
	{
		if (!file_exists($cache_file)) {
			return;
		}

		$pdo   = new PDO();
		$file  = new FileFormat($cache_file, $pdo);
		$datas = $file->parse();
		echo json_encode($datas);
		unset($file);
	}


}