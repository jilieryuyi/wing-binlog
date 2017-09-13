<?php namespace Wing\Bin\Auth;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:03
 */
class ServerInfo
{

    public $protocol_version = '';
	public $server_version = '';
	public $character_set = '';
	public $salt = '';
	public $connection_id = 0;
	public $auth_plugin_name = '';
	public $capability_flag;

    public static function parse($pack){
    	return new self($pack);
	}

    public function __construct($pack) 
	{
		var_dump($pack);
        $i = 0;
		$length = strlen($pack);
		//1byte协议版本号
       	$this->protocol_version = ord($pack[$i]);
        $i++;

        //version
        $start = $i;
        //服务器版本信息 以null(0x00)结束
        for ($i = $start; $i < $length; $i++) {
            if ($pack[$i] === chr(0)) {
                $i++;
                break;
            } else {
               $this->server_version .= $pack[$i];
            }
        }

        //connection_id 4 bytes 线程id
       	$this->connection_id = $pack[$i]. $pack[++$i] . $pack[++$i] . $pack[++$i];
        $i++;

		//8bytes加盐信息 用于握手认证
        for ($j = $i; $j < $i + 8; $j++) {
           	$this->salt .= $pack[$j];
        }
        $i = $i + 8;

        //1byte填充值 -- 0x00
        $i++;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        //2bytes服务器权能信息
		$this->capability_flag = $pack[$i]. $pack[$i+1];
        $i = $i + 2;


        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
       	//1byte字符编码
		$this->character_set = $pack[$i];

        $i++;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        //2byte服务器状态
		$i = $i + 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        //服务器权能标志 高16位
		$this->capability_flag = $pack[$i]. $pack[$i+1].$this->capability_flag;
		$i = $i + 2;


        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        //1byte加盐长度
		$salt_len = ord($pack[$i]);
        $i++;

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