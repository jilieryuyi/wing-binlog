<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:03
 */
class ServerInfo
{

    private $protocol_version = '';
    private $server_version = '';
    private $character_set = '';
    private $salt = '';
    private $connection_id = 0;
    private $auth_plugin_name = '';

    public function __construct($pack) 
	{

        $i = 0;
		$length = strlen($pack);
       	$this->protocol_version = ord($pack[$i]);
        $i++;

        //version
        $start = $i;
        for($i = $start; $i < $length; $i++) {
            if($pack[$i] === chr(0)) {
                $i++;
                break;
            } else{
               $this->server_version .= $pack[$i];
            }
        }

        //connection_id 4 bytes
       	$this->connection_id = $pack[$i]. $pack[++$i] . $pack[++$i] . $pack[++$i];
        $i++;

        //auth_plugin_data_part_1
        //[len=8] first 8 bytes of the auth-plugin data
        for($j = $i;$j<$i+8;$j++) {
           $this->salt .= $pack[$j];
        }
        $i = $i + 8;

        //filler_1 (1) -- 0x00
        $i++;

        //capability_flag_1 (2) -- lower 2 bytes of the Protocol::CapabilityFlags (optional)
        $i = $i + 2;

        //character_set (1) -- default server character-set, only the lower 8-bits Protocol::CharacterSet (optional)
       	$this->character_set = $pack[$i];

        $i++;

        //status_flags (2) -- Protocol::StatusFlags (optional)
        $i = $i + 2;

        //capability_flags_2 (2) -- upper 2 bytes of the Protocol::CapabilityFlags
        $i = $i + 2;


        //auth_plugin_data_len (1) -- length of the combined auth_plugin_data, if auth_plugin_data_len is > 0
        $salt_len = ord($pack[$i]);
        $i++;

        $salt_len = max(12, $salt_len - 9);

        //填充值
        $i = $i + 10;

        //next salt
        if ($length >= $i + $salt_len) {
            for($j = $i ;$j < $i + $salt_len;$j++) {
               $this->salt .= $pack[$j];
            }
        }

        $i = $i + $salt_len + 1;
        for($j = $i;$j<$length-1;$j++) {
           $this->auth_plugin_name .=$pack[$j];
        }
    }

    /**
     * @brief 获取salt auth
     * @return mixed
     */
    public function getSalt()
	{
        return $this->salt;
    }

    /**
     * @brief 获取编码
     * @return mixed
     */
    public function getCharSet()
	{
        return $this->character_set;
    }

    public function getVersion()
	{
        return $this->server_version;
    }
}