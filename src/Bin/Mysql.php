<?php namespace Wing\Bin;
use Wing\Bin\Constant\CommandType;

/**
 * Mysql.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:29
 * Email: huangxiaoan@xunlei.com
 */
class Mysql
{
	public static function query($sql) {
		$chunk_size = strlen($sql) + 1;
		$prelude    = pack('LC',$chunk_size, CommandType::COM_QUERY);
		Net::send($prelude . $sql);
		$res = Net::readPacket();
		return $res;
	}
}