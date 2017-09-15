<?php namespace Wing\Bin;
use Wing\Bin\Constant\CharacterSet;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 07:01
 * https://dev.mysql.com/doc/internals/en/connection-phase-packets.html#packet-Protocol::Handshake
 */
class PacketAuth
{
    // 2^24 - 1 16m
    const PACK_MAX_LENGTH = 16777215;
    // https://dev.mysql.com/doc/dev/mysql-server/latest/page_protocol_basic_ok_packet.html
    // 00 FE
    const OK_PACK_HEAD = [0, 254];
    // FF
    const ERR_PACK_HEAD = [255];


	/**
	 * 校验数据包格式是否正确，验证是否成功
	 *
	 * @param $pack
	 * @return bool
	 */
	public static function success($pack)
	{
		$head = ord($pack[0]);
		if (in_array($head, self::OK_PACK_HEAD)) {
			return true;
		}

		$error_code = unpack("v", $pack[1] . $pack[2])[1];
		$error_msg  = '';

		for ($i = 9; $i < strlen($pack); $i ++) {
			$error_msg .= $pack[$i];
		}
		var_dump(['code' => $error_code, 'msg' => $error_msg]);
		return false;
	}

}