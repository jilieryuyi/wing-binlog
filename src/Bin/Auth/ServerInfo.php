<?php namespace Wing\Bin\Auth;
use Wing\Bin\Constant\CharacterSet;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:03
 */
class ServerInfo
{

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
       	$this->protocol_version = ord($pack[$offset]);

        //服务器版本信息 以null(0x00)结束
        while ($pack[$offset++] !== chr(0x00)) {
			$this->server_info .= $pack[$offset];
		}

        //thread_id 4 bytes 线程id
       	$this->thread_id = unpack("V", substr($pack, $offset, 4))[1];
        $offset+=4;

		//8bytes加盐信息 用于握手认证
		$this->salt .= substr($pack,$offset,8);
        $offset = $offset + 8;

        //1byte填充值 -- 0x00
        $offset++;

        //2bytes 低位服务器权能信息
		$this->capability_flag = $pack[$offset]. $pack[$offset+1];
        $offset = $offset + 2;

       	//1byte字符编码
		$this->character_set = ord($pack[$offset]);

        $offset++;

        //2byte服务器状态
		//SERVER_STATUS_AUTOCOMMIT == 2
		$this->server_status = unpack("v", $pack[$offset].$pack[$offset+1])[1];
		$offset = $offset + 2;

        //服务器权能标志 高16位
		$this->capability_flag = unpack("V", $this->capability_flag.$pack[$offset]. $pack[$offset+1])[1];
		$offset = $offset + 2;

        //1byte加盐长度
		$salt_len = ord($pack[$offset]);
        $offset++;

        //mysql-server/sql/auth/sql_authentication.cc 2696 native_password_authenticate
        $salt_len = max(12, $salt_len - 9);

        //10bytes填充值 0x00
        $offset = $offset + 10;

        //第二部分加盐信息，至少12字符
        if ($length >= $offset + $salt_len) {
            for ($j = $offset;$j < $offset + $salt_len; $j++) {
               $this->salt .= $pack[$j];
            }
        }

        $offset = $offset + $salt_len + 1;
        for ($j = $offset; $j < $length - 1; $j++) {
           $this->auth_plugin_name .= $pack[$j];
        }
    }
}