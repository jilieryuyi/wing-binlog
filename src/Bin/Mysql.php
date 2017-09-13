<?php namespace Wing\Bin;
use Wing\Bin\Constant\CommandType;

/**
 * Mysql.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:29
 * Email: huangxiaoan@xunlei.com
 */
class Mysql
{
	public static function query($sql) {
		$chunk_size = strlen($sql) + 1;
		$prelude    = pack('LC',$chunk_size, CommandType::COM_QUERY);

		Net::send($prelude . $sql);

		$res   = Net::readPacket();
		$fbyte = ord($res[0]);

		if ($fbyte >= Packet::RESULT_SET_HEAD[0] && $fbyte <= Packet::RESULT_SET_HEAD[1]) {
            $data = $res;
            //一直读取直到遇到结束报文
            while (ord($res[0]) != Packet::EOF_HEAD) {
                $res = Net::readPacket();
                var_dump(ord($res[0]));
                $data .= $res;
            }

            //这里还需要对报文进行解析

            return $data;
        }
        return true;
	}

    public static function excute($sql) {
        $chunk_size = strlen($sql) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_PREPARE);
        Net::send($prelude . $sql);
        $res = Net::readPacket();

        $smtid = unpack("L", $res[1].$res[2].$res[3].chr(0))[1];
        echo "smtid=",$smtid,"\r\n";

        //cloumns count
        echo "cloumns count=".unpack("n", $res[4].$res[5].$res[6].$res[7])[1],"\r\n";


        $chunk_size = strlen($smtid) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_EXECUTE);
        Net::send($prelude . $smtid);
        $res = Net::readPacket();
        var_dump($res);


       // $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',1, CommandType::COM_STMT_FETCH);
        Net::send($prelude);
        $res = Net::readPacket();
        return $res;
    }

}