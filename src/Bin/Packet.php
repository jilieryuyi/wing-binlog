<?php namespace Wing\Bin;
use Wing\Bin\Constant\CharacterSet;
use Wing\Bin\Constant\CommandType;

/**
 * Packet.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:57
 * Email: huangxiaoan@xunlei.com
 *
 * mysql协议数据包处理
 * n，null terminated string，空字符标志结束
 * N，length coded binary，1-9字节前置编码长度
 * 规则如下：
 *  0-250	0	第一个字节值即为数据的真实长度
 *  251	    0	空数据，数据的真实长度为零
 *  252	    2	后续额外2个字节标识了数据的真实长度
 *  253	    3	后续额外3个字节标识了数据的真实长度
 *  254	    8	后续额外8个字节标识了数据的真实长度
 *
 *
/**
Return OK to the client.

The OK packet has the following structure:

Here 'n' denotes the length of state change information.

Bytes                Name
-----                ----
1                    [00] or [FE] the OK header
[FE] is used as header for result set rows
1-9 (lenenc-int)     affected rows
1-9 (lenenc-int)     last-insert-id

if capabilities & CLIENT_PROTOCOL_41 {
2                  status_flags; Copy of thd->server_status; Can be used
by client to check if we are inside a transaction.
2                  warnings (New in 4.1 protocol)
} elseif capabilities & CLIENT_TRANSACTIONS {
2                  status_flags
}

if capabilities & CLIENT_ACCEPTS_SERVER_STATUS_CHANGE_INFO {
1-9(lenenc_str)    info (message); Stored as length of the message string +
message.
if n > 0 {
1-9 (lenenc_int) total length of session state change
information to follow (= n)
n                session state change information
}
}
else {
string[EOF]          info (message); Stored as packed length (1-9 bytes) +
message. Is not stored if no message.
}

 *
 *
 *
 *
 *
 *
 *  Send eof (= end of result set) to the client.

The eof packet has the following structure:

- 254           : Marker (1 byte)
- warning_count : Stored in 2 bytes; New in 4.1 protocol
- status_flag   : Stored in 2 bytes;
For flags like SERVER_MORE_RESULTS_EXISTS.

Note that the warning count will not be sent if 'no_flush' is set as
we don't want to report the warning count until all data is sent to the
client.

 *
 * /Users/yuyi/Web/xiaoan/wing/src/Binlog/tests/tidb/_vendor/src/github.com/go-sql-driver/mysql/packets.go
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

	private $packet;
	private $pos;
	private $len;

	/**
	 * http://boytnt.blog.51cto.com/966121/1279318
     * 生成auth认证包
     *
	 * @param int $flag 服务器权能标志
	 * @param string $user 数据库用户
	 * @param string $pass 数据库密码
	 * @param string $salt 密码加密加盐
	 * @param string $db 数据库
	 * @return string
	 */
	public static function  getAuth($flag, $user, $pass, $salt, $db = '')
	{
		$data 	= pack('L',$flag);						 	//4bytes权能信息
		$data  .= pack('L', self::PACK_MAX_LENGTH); 	//4bytes最大长度
		$data  .= chr(CharacterSet::utf8_general_ci);			//1byte字符编码

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

	/**
	 * COM_BINLOG_DUMP封包
     *
     * @param string $binlog_file
     * @param int $pos
     * @param int $slave_server_id
     * @return string
     */
	public static function binlogDump($binlog_file,$pos, $slave_server_id)
    {
        $header = pack('l', 11 + strlen($binlog_file));
        $data   = $header . chr(CommandType::COM_BINLOG_DUMP);
        $data  .= pack('L', $pos);
        $data  .= pack('s', 0);
        $data  .= pack('L', $slave_server_id);
        $data  .= $binlog_file;

        return $data;
    }

    /**
     * COM_REGISTER_SLAVE封包
     *
     * @param int $slave_server_id
     * @return string
     */
    public static function registerSlave($slave_server_id)
    {
        $header   = pack('l', 18);

        // COM_BINLOG_DUMP
        $data  = $header . chr(CommandType::COM_REGISTER_SLAVE);
        $data .= pack('L', $slave_server_id);
        $data .= chr(0);
        $data .= chr(0);
        $data .= chr(0);

        $data .= pack('s', '');

        $data .= pack('L', 0);
        $data .= pack('L', 1);

        return $data;
    }

    /**
     * COM_QUERY封包，此命令最常用，常用于增删改查
     *
     * @param string $sql
     * @return string
     */
    public static function query($sql)
    {
        $chunk_size = strlen($sql) + 1;
        return pack('LC',$chunk_size, CommandType::COM_QUERY).$sql;
    }

    /**
     * 数据包校验
     *
     * @param string $pack
     * @throws \Exception
     */
    public static function success($pack)
    {
        if (ord($pack[0]) == self::OK_PACK_HEAD) {
            return;
        }

        $error_code = unpack("v", $pack[1] . $pack[2])[1];
        $error_msg  = '';

        for ($i = 9; $i < strlen($pack); $i ++) {
            $error_msg .= $pack[$i];
        }
        throw new \Exception($error_msg, $error_code);
    }

    /**
     * 包处理构造函数
     *
     * @param string $packet
     */
    public function __construct($packet)
    {
        $this->packet = $packet;
        $this->pos    = 0;
        $this->len    = strlen($packet);
    }

    /**
     * 读取固定字节
     *
     * @param int $bytes
     * @return string
     */
    public function read($bytes)
    {
        $sub_str = substr($this->packet, $this->pos, $bytes);
        $this->pos += $bytes;
        return $sub_str;
    }

    public function readUint8()
    {
        $res = $this->read(1);
        return unpack("C", $res)[1];
    }
    //小端序16bit
    public function readUint16()
    {
        $_server_status = self::read(2);
        return unpack("v", $_server_status[0].$_server_status[1])[1];
    }
    public function readUint24()
    {
        $res = $this->read(3);
        $data = unpack("C3", $res[0].$res[1].$res[2]);//[1];
        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
    }
    public function readUint64()
    {
        $res = $this->read(8);
        return unpack("P", $res)[1];
    }
    public function readDatetime()
    {
        /**
        https://dev.mysql.com/doc/internals/en/myisam-column-attributes.html
        https://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
        DATETIME
        Storage: eight bytes.
        Part 1 is a 32-bit integer containing year*10000 + month*100 + day.
        Part 2 is a 32-bit integer containing hour*10000 + minute*100 + second.
        Example: a DATETIME column for '0001-01-01 01:01:01' looks like: hexadecimal B5 2E 11 5A 02 00 00 00
         */

        //libmysql/libmysql.c 3176 read_binary_datetime
        //第一个字节获取日期的存储长度
        $length = $this->getLength();

        $year = $this->readUint16();

        $month = $this->readUint8();
        $day = $this->readUint8();
        $hour = $this->readUint8();
        $minute = $this->readUint8();
        $second = $this->readUint8();

        return sprintf("%d-%02d-02%d %02d:%02d:%02d",$year, $month, $day,
            $hour, $minute, $second);
    }
    public function readUint32()
    {
        $res = $this->read(4);
        return unpack("V", $res)[1];
    }

    /**
     * 获取数据长度
     *
     * @param int
     */
    public function getLength()
    {
        $len = ord($this->packet[$this->pos]);
        $this->pos++;

        //如果第一个字节为251，则实际数据包长度为0
        if ($len == 251) {
            return 0;
        }

        //如果第一个字节为252，实际长度存储在后续两个字节当中
        //v解包，unsigned short (always 16 bit, little endian byte order)
        if ($len == 252) {
            $len = unpack("v", $this->packet[$this->pos].$this->packet[$this->pos+1])[1];
            $this->pos += 2;
            return $len;
        }

        //如果第一个字节是253，实际长度存储在后续的三个字节当中
        //C3解包，小端序，后续字节移位8和移位16相加则为实际的结果
        if ($len == 253) {
            $data = unpack("C3", $this->packet[$this->pos].$this->packet[$this->pos+1].$this->packet[$this->pos+2]);//[1];
            $len  = $data[1] + ($data[2] << 8) + ($data[3] << 16);
            $this->pos += 3;
            return $len;
        }

        //如果第一个字节为254，实际长度存储在后续的8字节当中
        //小端序，P解包
        if ($len == 254) {
            $len = unpack("P", $this->packet[$this->pos]. $this->packet[$this->pos+1]. $this->packet[$this->pos+2]. $this->packet[$this->pos+3]. $this->packet[$this->pos+4]. $this->packet[$this->pos+5]. $this->packet[$this->pos+6]. $this->packet[$this->pos+7])[1];
            $this->pos += 8;
            return $len;
        }

        //否则第一个字节就是真实的数据长度
        return $len;
    }

    /**
     * 自动化，获取下一个数据包
     *
     * @param string
     */
    public function next()
    {
        if ($this->pos >= $this->len) {
            return null;
        }
        $len = $this->getLength();
        return $this->read($len);
    }

    public function debugDump()
    {
    	//var_dump($this->packet);
    	//echo "\r\n\r\n";

        for ($i = 0; $i < $this->len; $i++) {
            echo ord($this->packet[$i]),"(".$this->packet[$i].")-";
        }
        echo "\r\n\r\n";
    }

    public function getColumns()
    {
        /**
        n	目录名称（Length Coded String）
        n	数据库名称（Length Coded String）
        n	数据表名称（Length Coded String）
        n	数据表原始名称（Length Coded String）
        n	列（字段）名称（Length Coded String）
        4	列（字段）原始名称（Length Coded String）
        1	填充值
        2	字符编码
        4	列（字段）长度
        1	列（字段）类型
        2	列（字段）标志
        1	整型值精度
        2	填充值（0x00）
        n	默认值（Length Coded String）
         */
        $column = [
            "dir" => $this->next(),
            "database"=>$this->next(),
            "table"=>$this->next(),
            "table_alias"=>$this->next(),
            "column"=>$this->next(),
            "column_alias"=>$this->next(),
        ];

        $this->read(1);
        $column["character_set"] = $this->readUint16();
        $column["length"] = $this->readUint32();
        $type = $this->readUint8();
        $column["type"] = $type;
        $column["type_text"] = FieldType::fieldtype2str($type);
        $column["flag"] = $this->readUint16();
        $column["precision"] = $this->readUint8();
        $this->read(2);
        $column["default"] = $this->next();
        return $column;
    }

    ///Users/yuyi/Downloads/mysql-5.7.19/sql-common/pack.c 93
    public static function storeLength($length)
    {
        if ($length < 251) {
            return chr($length);
        }

        /* 251 is reserved for NULL */
        if ($length < 65536) {
            return chr(252).chr($length).chr($length >> 8);
            //pack("v", $length);
        }

        if ($length < 16777216) {
            $data = chr(253);
            $data .= chr($length).chr($length >> 8).chr($length >> 16);
            return $data;
        }

        return chr(254).pack("P", $length);
    }

    private static $sequence = 0;
    public static function writeCommand($command, $packet = '')
	{
		self::$sequence = 0;
		$length = strlen($packet)+1;
		$ll     = chr($length) . chr($length >> 8) . chr($length >> 16);
		$data   = $ll.chr(self::$sequence).pack('C', $command).$packet;
		$res = ($length + 4) == Net::send($data);
		//if ($res) self::$sequence++;
		return $res;
	}
}