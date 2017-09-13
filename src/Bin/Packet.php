<?php namespace Wing\Bin;
/**
 * Packet.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:57
 * Email: huangxiaoan@xunlei.com
 */
class Packet
{
	/**
	 * 响应报文类型	第1个字节取值范围
		OK 			响应报文	0x00
		Error 		响应报文	0xFF
		Result Set 	报文	0x01 - 0xFA
		Field 		报文	0x01 - 0xFA
		Row Data 	报文	0x01 - 0xFA
		EOF 		报文	0xFE
	 */
	const PACK_MAX_LENGTH 	= 16777215;
	const OK_PACK_HEAD  	= 0x00;
	const ERR_PACK_HEAD 	= 0xff;
	const RESULT_SET_HEAD 	= [0x01, 0xfa];
	const FIELD_HEAD 		= [0x01, 0xfa];
	const ROW_DATA_HEAD 	= [0x01, 0xfa];
	const EOF_HEAD 			= 0xfe;

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
}