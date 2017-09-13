<?php namespace Wing\Bin;
use Wing\Library\PDO;

/**
 * Db.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:13
 * Email: huangxiaoan@xunlei.com
 */
class Db
{
	/**
	 * @var PDO
	 */
	public static $pdo;
	public static function getChecksum()
	{
		$res = self::$pdo->row("SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'");
		return $res['Value'];
	}

	public static function getPos() {
		$sql    = "SHOW MASTER STATUS";
		$result = self::$pdo->row($sql);
		return $result;
	}
}