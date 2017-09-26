<?php namespace Wing\Bin\Auth;
use Wing\Bin\Constant\CharacterSet;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:03
 *
 * @document https://mariadb.com/kb/en/library/1-connecting-connecting/#initial-handshake-packet
 *
	int<1> protocol version
	string<NUL> server version (MariaDB server version is by default prefixed by "5.5.5-")
	int<4> connection id
	string<8> scramble 1st part (authentication seed)
	string<1> reserved byte
	int<2> server capabilities (1st part)
	int<1> server default collation
	int<2> status flags
	int<2> server capabilities (2nd part)
	int<1> length of scramble's 2nd part
	if (server_capabilities & PLUGIN_AUTH)
	int<1> plugin data length
	else
	int<1> 0x00
	string<6> filler
	if (server_capabilities & CLIENT_MYSQL)
	string<4> filler
	else
	int<4> server capabilities 3rd part . MariaDB specific flags /-- MariaDB 10.2 or later
	if (server_capabilities & CLIENT_SECURE_CONNECTION)
		string<n> scramble 2nd part . Length = max(12, plugin data length - 9)
	string<1> reserved byte
	if (server_capabilities & PLUGIN_AUTH)
		string<NUL> authentication plugin name
 */
class ServerInfo
{
	const CLIENT_PLUGIN_AUTH =  (1 << 19);
	/*服务协议版本号：该值由 PROTOCOL_VERSION
	宏定义决定（参考MySQL源代码/include/mysql_version.h头文件定义）
	mysql-server/config.h.cmake 399
	*/
    public $protocol_version = '';
    /**
	 * @var string $server_info
	 * mysql-server/include/mysql_version.h.in 12行
	 * mysql-server/cmake/mysql_version.cmake 59行
	 */
	public $server_info = '';
	public $character_set = '';
	public $salt = '';
	public $thread_id = 0;
	//mysql-server/sql/auth/sql_authentication.cc 567行
	public $auth_plugin_name = '';
	//mysql-server/include/mysql_com.h 204 238
	//mysql初始化的权能信息为 CapabilityFlag::CLIENT_BASIC_FLAGS
	public $capability_flag;
	//define Wing\Bin\Constant\ServerStatus
	public $server_status;

    public static function parse($pack){
    	return new self($pack);
	}

    public function __construct($pack) 
	{
        $offset = 0;
		$length = strlen($pack);
		//1byte协议版本号
		//int<1> protocol version
       	$this->protocol_version = ord($pack[$offset]);

       	//string<NUL> server version (MariaDB server version is by default prefixed by "5.5.5-")
		//服务器版本信息 以null(0x00)结束
        while ($pack[$offset++] !== chr(0x00)) {
			$this->server_info .= $pack[$offset];
		}

		//int<4> connection id
        //thread_id 4 bytes 线程id
       	$this->thread_id = unpack("V", substr($pack, $offset, 4))[1];
        $offset += 4;

        //string<8> scramble 1st part (authentication seed)
		//8bytes加盐信息 用于握手认证
		$this->salt .= substr($pack, $offset,8);
        $offset = $offset + 8;

        //string<1> reserved byte 1byte保留值
        //1byte填充值 -- 0x00
        $offset++;

        //int<2> server capabilities (1st part)
        //2bytes 低位服务器权能信息
		$this->capability_flag = $pack[$offset]. $pack[$offset+1];
        $offset = $offset + 2;

        //int<1> server default collation
       	//1byte字符编码
		$this->character_set = ord($pack[$offset]);
        $offset++;

        //int<2> status flags
        //2byte服务器状态
		//SERVER_STATUS_AUTOCOMMIT == 2
		$this->server_status = unpack("v", $pack[$offset].$pack[$offset+1])[1];
		$offset += 2;

		//int<2> server capabilities (2nd part)
        //服务器权能标志 高16位
		$this->capability_flag = unpack("V", $this->capability_flag.$pack[$offset]. $pack[$offset+1])[1];
		$offset += 2;

		//int<1> length of scramble's 2nd part
        //1byte加盐长度
		$salt_len = ord($pack[$offset]);
        $offset++;

        /**
			if (server_capabilities & PLUGIN_AUTH)
			int<1> plugin data length
			else
			int<1> 0x00
		 */
		/*$plugin_data_length = 0;
		if ($this->capability_flag & self::CLIENT_PLUGIN_AUTH) {
			//int<1> plugin data length
			$plugin_data_length = ord($offset);
			//$offset++;
		} else {
			//int<1> 0x00
			//$offset++;
		}*/

		//string<6> filler
		//$offset += 6;

		//	if (server_capabilities & CLIENT_MYSQL)
		//		string<4> filler
		//	else
		//	int<4> server capabilities 3rd part . MariaDB specific flags /-- MariaDB 10.2 or later
		//
		//$offset += 4;


        //mysql-server/sql/auth/sql_authentication.cc 2696 native_password_authenticate
        $salt_len = max(12, $salt_len - 9);

        //10bytes填充值 0x00
        $offset = $offset + 10;

		/**
		  if (server_capabilities & CLIENT_SECURE_CONNECTION)
		  string<n> scramble 2nd part . Length = max(12, plugin data length - 9)
		 */
        //第二部分加盐信息，至少12字符
		$this->salt .= substr($pack, $offset, $salt_len);
		$offset += $salt_len;

		//string<1> reserved byte
		$offset += 1;

		//if (server_capabilities & PLUGIN_AUTH)
		//	string<NUL> authentication plugin name
		//$length - 1 去除null字符
		$len = $length-$offset-1;
		if ($len > 0) {
			$this->auth_plugin_name = substr($pack,$offset, $len);
		}
    }
}