<?php namespace Wing\Bin;
/**
 * Net.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:09
 * Email: huangxiaoan@xunlei.com
 */
class Net
{
	public static $socket;
	public static function send($data)
	{
		if(socket_write(self::$socket, $data, strlen($data))=== false ) {
			throw new \Exception( sprintf( "Unable to write to socket: %s", socket_strerror( socket_last_error())));
		}
		return true;
	}
	private static function _readBytes($data_len)
	{

		// server gone away
		if ($data_len == 5) {
			throw new \Exception('read 5 bytes from mysql server has gone away');
		}

		try{
			$bytes_read = 0;
			$body       = '';
			while ($bytes_read < $data_len) {
				$resp = socket_read(self::$socket, $data_len - $bytes_read);

				//
				if($resp === false) {
					throw new \Exception(
						sprintf(
							'remote host has closed. error:%s, msg:%s',
							socket_last_error(),
							socket_strerror(socket_last_error())
						));
				}

				// server kill connection or server gone away
				if(strlen($resp) === 0){
					throw new \Exception("read less " . ($data_len - strlen($body)));
				}
				$body .= $resp;
				$bytes_read += strlen($resp);
			}
			if (strlen($body) < $data_len){
				throw new \Exception("read less " . ($data_len - strlen($body)));
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
		//var_dump("readPacket=>1=>",$header);
		//消息体长度3bytes 小端序
		$unpack_data = unpack("L",$header[0].$header[1].$header[2].chr(0))[1];
		//var_dump("readPacket=>2=>",$unpack_data);
		$Sequence_id = $header[3];
		// var_dump("readPacket=>3=>",ord($header[0]) | ord($header[1])<<8 | ord($header[2])<<16);


		$result = self::_readBytes($unpack_data);
		//var_dump("readPacket=>4=>",$result);
		return $result;
	}

}