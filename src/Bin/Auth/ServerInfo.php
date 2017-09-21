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
	/**
	状态名称	状态值
	SERVER_STATUS_IN_TRANS	0x0001
	SERVER_STATUS_AUTOCOMMIT	0x0002
	SERVER_STATUS_CURSOR_EXISTS	0x0040
	SERVER_STATUS_LAST_ROW_SENT	0x0080
	SERVER_STATUS_DB_DROPPED	0x0100
	SERVER_STATUS_NO_BACKSLASH_ESCAPES	0x0200
	SERVER_STATUS_METADATA_CHANGED	0x0400
	 */
	public $server_status;

    public static function parse($pack){
    	return new self($pack);
	}

    public function __construct($pack) 
	{
		//var_dump($pack);
        $i = 0;
		$length = strlen($pack);
		//1byte协议版本号
       	$this->protocol_version = ord($pack[$i]);

        //服务器版本信息 以null(0x00)结束
        while ($pack[$i++] !== chr(0x00)) {
			$this->server_info .= $pack[$i];
		}

        //thread_id 4 bytes 线程id
       	$this->thread_id = unpack("V", substr($pack, $i, 4))[1];
        $i+=4;

		//8bytes加盐信息 用于握手认证
		$this->salt .= substr($pack,$i,8);//;[$j];
        $i = $i + 8;

        //1byte填充值 -- 0x00
        $i++;

        //2bytes 低位服务器权能信息
		$this->capability_flag = $pack[$i]. $pack[$i+1];
        $i = $i + 2;

       	//1byte字符编码
		$this->character_set = ord($pack[$i]);

        $i++;

        //2byte服务器状态
		//SERVER_STATUS_AUTOCOMMIT == 2
		$this->server_status = unpack("v", $pack[$i].$pack[$i+1])[1];
		$i = $i + 2;

        //服务器权能标志 高16位
		$this->capability_flag = unpack("V", $this->capability_flag.$pack[$i]. $pack[$i+1])[1];
		$i = $i + 2;

        //1byte加盐长度
		$salt_len = ord($pack[$i]);
        $i++;

        //mysql-server/sql/auth/sql_authentication.cc 2696 native_password_authenticate
        $salt_len = max(12, $salt_len - 9);

        //10bytes填充值 0x00
        $i = $i + 10;

        //第二部分加盐信息，至少12字符
        if ($length >= $i + $salt_len) {
            for($j = $i ;$j < $i + $salt_len;$j++) {
               $this->salt .= $pack[$j];
            }
        }

        $i = $i + $salt_len + 1;
        for ($j = $i; $j < $length - 1; $j++) {
           $this->auth_plugin_name .= $pack[$j];
        }
    }
}