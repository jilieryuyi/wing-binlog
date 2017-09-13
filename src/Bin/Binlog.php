<?php namespace Wing\Bin;
/**
 * Binlog.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:13
 * Email: huangxiaoan@xunlei.com
 */
class Binlog
{
	/**
	 * 注册成slave
	 * @param int $slave_server_id
	 */
	public static function registerSlave($slave_server_id)
	{
		$header   = pack('l', 18);

		// COM_BINLOG_DUMP
		$data  = $header . chr(ConstCommand::COM_REGISTER_SLAVE);
		$data .= pack('L', $slave_server_id);
		$data .= chr(0);
		$data .= chr(0);
		$data .= chr(0);

		$data .= pack('s', '');

		$data .= pack('L', 0);
		$data .= pack('L', 1);

		Net::send($data);

		$result = Net::readPacket();
		PacketAuth::success($result);
	}
}