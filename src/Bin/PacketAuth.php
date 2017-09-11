<?php namespace Wing\Bin;
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
	 * http://boytnt.blog.51cto.com/966121/1279318
	 * @param $flag
	 * @param $user
	 * @param $pass
	 * @param $salt
	 * @param string $db
	 * @return string
	 */
	public static function  getAuthPack($flag, $user, $pass, $salt, $db = '')
	{

		$data = pack('L',$flag);

		// max-length 4bytes，最大16M 占3bytes
		$data .= pack('L', self::PACK_MAX_LENGTH);


		// Charset  1byte utf8=>33
		$data .= chr(MysqlChartSet::utf8_general_ci);


		// 空 bytes23
		for ($i = 0; $i < 23; $i++) {
			$data .=chr(0);
		}

		// http://dev.mysql.com/doc/internals/en/secure-password-authentication.html#packet-Authentication::Native41
		$result = sha1($pass, true) ^ sha1($salt . sha1(sha1($pass, true), true),true);

		//转码 8是 latin1
		//$user = iconv('utf8', 'latin1', $user);

		//
		$data = $data . $user . chr(0) . chr(strlen($result)) . $result;
		if ($db) {
			$data .= $db . chr(0);
		}

		// V L 小端，little endian
		$str = pack("L", strlen($data));
		$s = $str[0].$str[1].$str[2];

		$data = $s . chr(1) . $data;

		return $data;
	}

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