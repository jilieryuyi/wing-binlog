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
		$data 	= pack('L',$flag);						 	//4bytes权能信息
		$data  .= pack('L', self::PACK_MAX_LENGTH); 	//4bytes最大长度
		$data  .= chr(MysqlChartSet::utf8_general_ci);			//1byte字符编码

		//填充23字节0x00
		for ($i = 0; $i < 23; $i++) {
			$data .= chr(0);
		}

		//用户名 0x00 以NULL结束
		$data   .= $user . chr(0) ;
		//密码加密
		$result  = sha1($pass, true) ^ sha1($salt . sha1(sha1($pass, true), true),true);
		//密码信息 Length Coded Binary
		$data 	.= chr(strlen($result)) . $result;

		//数据库名称  0x00 以NULL结束
		if ($db) {
			$data .= $db . chr(0);
		}

		$str  = pack("L", strlen($data));
		//报文结构生成
		//$str[0].$str[1].$str[2] 为消息长度 chr(1)为序号信息必须为1 $data 部分为消息体
		//$str[0].$str[1].$str[2] . chr(1) 占4bytes 为消息头
		$data = $str[0].$str[1].$str[2] . chr(1) . $data;

		return $data;
	}
}