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
	public static $pdo = null;

	private static function PdoInit()
	{
		if (!self::$pdo) {
			self::$pdo = new \Wing\Library\PDO();
		}
	}

	public static function getChecksum()
	{
		self::PdoInit();
		$res = self::$pdo->row("SHOW GLOBAL VARIABLES LIKE 'BINLOG_CHECKSUM'");
		return $res['Value'];
	}

	public static function getPos()
	{
		self::PdoInit();
		$sql    = "SHOW MASTER STATUS";
		$result = self::$pdo->row($sql);
		return $result;
	}

	public static function getFields($schema, $table)
	{
		self::PdoInit();
		$sql = "SELECT
                COLUMN_NAME,COLLATION_NAME,CHARACTER_SET_NAME,COLUMN_COMMENT,COLUMN_TYPE,COLUMN_KEY
                FROM
                information_schema.columns
                WHERE
                table_schema = '{$schema}' AND table_name = '{$table}'";
		$result = self::$pdo->query($sql);
		return $result;
	}
}