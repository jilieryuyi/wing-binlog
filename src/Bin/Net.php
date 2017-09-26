<?php namespace Wing\Bin;
use Wing\Exception\NetCloseException;

/**
 * Net.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:09
 * Email: huangxiaoan@xunlei.com
 */
class Net
{
	public static $socket = null;
	public static function send($data)
	{
		if (($bytes = socket_write(self::$socket, $data, strlen($data))) === false ) {
			$error_code = socket_last_error();
			throw new \Exception( sprintf( "Unable to write to socket: %s", socket_strerror($error_code)), $error_code);
		}
		return $bytes === strlen($data);
	}
	public static function _readBytes($data_len)
	{

		// server gone away
//		if ($data_len == 5) {
//			throw new \Exception('read 5 bytes from mysql server has gone away');
//		}

		try{
			$bytes_read = 0;
			$body       = '';
			while ($bytes_read < $data_len) {
				$resp = socket_read(self::$socket, $data_len - $bytes_read);
				if($resp === false) {
					throw new NetCloseException(
						sprintf(
							'remote host has closed. error:%s, msg:%s',
							socket_last_error(),
							socket_strerror(socket_last_error())
						));
				}

				// server kill connection or server gone away
				if(strlen($resp) === 0){
					throw new NetCloseException("read less " . ($data_len - strlen($body)));
				}
				$body .= $resp;
				$bytes_read += strlen($resp);
			}
			if (strlen($body) < $data_len){
				throw new NetCloseException("read less " . ($data_len - strlen($body)));
			}
			return $body;
		} catch (\Exception $e) {
			var_dump($e->getMessage());
		}
		return null;
	}

	public static function readPacket()
	{
		//消息头
		$header = self::_readBytes(4);
		//消息体长度3bytes 小端序
		$unpack_data = unpack("L",$header[0].$header[1].$header[2].chr(0))[1];
		//$sequence_id = $header[3];
		$result = self::_readBytes($unpack_data);
		return $result;
	}

}