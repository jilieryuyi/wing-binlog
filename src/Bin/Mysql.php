<?php namespace Wing\Bin;

use Wing\Bin\Constant\CommandType;
use Wing\Bin\Constant\Cursor;
use Wing\Bin\Constant\FieldFlag;
use Wing\Bin\Constant\FieldType;

/**
 * Mysql.php
 * User: huangxiaoan
 * Created: 2017/9/13 17:29
 * Email: huangxiaoan@xunlei.com
 */
class Mysql
{
    public static $rows_affected  = 0;
    public static $last_insert_id = 0;
    public static $server_status;
    public static $debug = false;

    public static function close()
	{
		//COM_QUIT
		$packet =  pack('VC',1, CommandType::COM_QUIT);
		return Net::send($packet);
	}

	public static function query($sql)
    {
		$packet = Packet::query($sql);
		Net::send($packet);

		$res   = Net::readPacket();
		$fbyte = ord($res[0]);

		//这里可能是三种类型的报文 Result set、Field和Row Data
        //以下解析 Result set
		if ($fbyte >= Packet::RESULT_SET_HEAD[0] && $fbyte <= Packet::RESULT_SET_HEAD[1]) {
            if (self::$debug) {
            	//var_dump("Result set");
			}
			//列数量
		    $column_num = $fbyte;

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

            目录名称：在4.1及之后的版本中，该字段值为"def"。
            数据库名称：数据库名称标识。
            数据表名称：数据表的别名（AS之后的名称）。
            数据表原始名称：数据表的原始名称（AS之前的名称）。
            列（字段）名称：列（字段）的别名（AS之后的名称）。
            列（字段）原始名称：列（字段）的原始名称（AS之前的名称）。
            字符编码：列（字段）的字符编码值。
            列（字段）长度：列（字段）的长度值，真实长度可能小于该值，例如VARCHAR(2)类型的字段实际只能存储1个字符。
            列（字段）类型：列（字段）的类型值，取值范围如下（参考源代码/include/mysql_com.h头文件中的enum_field_type枚举类型定义
             */

		    //列信息
		    $columns = [];
            //一直读取直到遇到结束报文
            while (1) {
                $res = Net::readPacket();

				if (ord($res[0]) == Packet::EOF_HEAD) {
				    break;
                }

                $packet = new Packet($res);
                //$column['dir_name']         = $packet->next();
                //$column['database_name']    = $packet->next();
                //$column['table_name']       = $packet->next();
                //$column['old_table_name']   = $packet->next();
                //$column['column_name']      = $columns[] = $packet->next();

                //def 移动游标至下一个
                $packet->next();
                //数据库名称
                $packet->next();
                //数据表名称
                $packet->next();
                //原数据表名称
                $packet->next();
                //列名称 这个才是我们要的
                $columns[] = $packet->next();
                unset($packet);
            }

            //行信息
            $rows = [];
            //一直读取直到遇到结束报文
            while (1) {
                $res = Net::readPacket();
                if (ord($res[0]) == Packet::EOF_HEAD) {
                    break;
                }
                $index = 0;
                $row   = [];

                $packet = new Packet($res);
                while($index < $column_num) {
                    $row[$columns[$index++]] = $packet->next();
                }
                unset($packet, $res);
				$rows[] = $row;
            }
            return $rows;
        }

        else if ($fbyte == Packet::OK_PACK_HEAD) {
			if (self::$debug) {
				//var_dump("OK报文");
			}
		    //1byte 0x00 OK报文 恒定为0x00
            //1-9bytes 受影响的行数
            //1-9bytes 索引id，执行多个insert时，默认是第一个
            //2bytes 服务器状态
            //2bytes 告警计数
            //nbytes 服务器消息，无结束符号，直接读取到尾部

			/**
			1	OK报文，值恒为0x00
			1-9	受影响行数（Length Coded Binary）
			1-9	索引ID值（Length Coded Binary）
			2	服务器状态
			2	告警计数
			n	服务器消息（字符串到达消息尾部时结束，无结束符，可选）

            受影响行数：当执行INSERT/UPDATE/DELETE语句时所影响的数据行数。
			索引ID值：该值为AUTO_INCREMENT索引字段生成，如果没有索引字段，则为0x00。
            注意：当INSERT插入语句为多行数据时，该索引ID值为第一个插入的数据行索引值，而非最后一个。
			服务器状态：客户端可以通过该值检查命令是否在事务处理中。
			告警计数：告警发生的次数。
			服务器消息：服务器返回给客户端的消息，一般为简单的描述性字符串，可选字段。
			 */

			$packet = new Packet($res);
			//报头1byte
			$packet->read(1);

			//length coded binary
            self::$rows_affected  = $packet->getLength();
            self::$last_insert_id = $packet->getLength();

            //db 状态
            self::$server_status = $packet->readUint16();
            //var_dump($server_status);

            //var_dump(self::$rows_affected,self::$last_insert_id, $server_status);

            return true;
        }

        else if ($fbyte == Packet::ERR_PACK_HEAD) {
			//var_dump("Error报文");
		    //1byte Error报文 恒定为0xff
            //2bytes 错误编号，小字节序
            //1byte 服务器状态标志，恒为#字符
            //5bytes 服务器状态
            //nbytes 服务器消息
            $error_code = unpack("v", $res[1] . $res[2])[1];
            $error_msg  = '';
            //第9个字符到结束的字符，均为错误消息
            for ($i = 9; $i < strlen($res); $i ++) {
                $error_msg .= $res[$i];
            }
            throw new \Exception($error_msg, $error_code);
        }

        return true;
	}

	/**
	 * 预处理执行sql
	 * @see https://dev.mysql.com/doc/internals/en/myisam-column-attributes.html
	 */
    public static function execute($sql,array $params = [])
    {
        //COM_STMT_PREPARE --- start ---
        /**
        COM_STMT_PREPARE
        结构	说明
        [PREPARE_OK]	PREPARE_OK结构
        如果参数数量大于0
        [Field]	与Result Set消息结构相同
        [EOF]
        如果列数大于0
        [Field]	与Result Set消息结构相同
        [EOF]
         */
        //$chunk_size = strlen($sql) + 1;
        //$prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_PREPARE);
        //Net::send($prelude . $sql);
        $success = Packet::writeCommand(CommandType::COM_STMT_PREPARE, $sql);
        if (!$success) {
        	echo "write command COM_STMT_PREPARE failure\r\n";
        	return false;
		}
        $res = Net::readPacket();

        /**PREPARE_OK 结构
        1	OK报文，值为0x00
        4	预处理语句ID值
        2	列数量
        2	参数数量
        1	填充值（0x00）
        2	告警计数
         */
        Packet::success($res);
        $packet = new Packet($res);
        //$packet->debugDump();
        $packet->read(1);//ok包头

        //预处理语句ID值
        $smtid = $packet->readUint32();//unpack("L", $res[1].$res[2].$res[3].chr(0))[1];
        echo "smtid=",$smtid,"\r\n";

        //列数量columns count
        $columns_count = $packet->readUint16();
        echo "列数量columns count=",$columns_count,"\r\n";
        //参数数量params count
        echo "参数数量params count=",$packet->readUint16(),"\r\n";
        //填充值（0x00）
        $packet->read(1);
        //告警计数warnings count
        echo "告警计数warnings count=",$packet->readUint16(),"\r\n";

        //参数响应包 暂时还不知道这个有什么用
        /**
            2	类型
            2	标志
            1	数值精度
            4	字段长度
         */

        $params_len = count($params);
        if ($params_len > 0) {
            $params_res = [];
            for ($i = 0; $i < $params_len; $i++) {
                $res = Net::readPacket();
                $packet = new Packet($res);
                //$packet->debugDump();
                $params_res[] = $packet->getColumns();
                unset($packet);
            }
            //EOF
            $res = Net::readPacket();
            //(new Packet($res))->debugDump();
            //var_dump($params_res);
        }


        if ($columns_count > 0) {
            //响应列包
            //列信息
            $columns = [];
            $cc = 0;
            //一直读取直到遇到结束报文
            while ($cc < $columns_count) {
                $cc++;
                $res = Net::readPacket();
                $packet = new Packet($res);
                //$packet->debugDump();
                //echo "\r\n";
                $column = $packet->getColumns();
                $columns[] = $column["column"];
                unset($packet);
            }
            //var_dump($columns);
            //EOF
            $res = Net::readPacket();
           // (new Packet($res))->debugDump();
            //COM_STMT_PREPARE --- end ---
        }


        /**
        字节	说明
        4	预处理语句的ID值
        1	标志位
            0x00: CURSOR_TYPE_NO_CURSOR
            0x01: CURSOR_TYPE_READ_ONLY
            0x02: CURSOR_TYPE_FOR_UPDATE
            0x04: CURSOR_TYPE_SCROLLABLE
        4	保留（值恒为0x01）

        如果参数数量大于0
        n	空位图（Null-Bitmap，长度 = (参数数量 + 7) / 8 字节）
        1	参数分隔标志

        如果参数分隔标志值为1
        n	每个参数的类型值（长度 = 参数数量 * 2 字节）
        n	每个参数的值
         */


        //$data  = pack('C', CommandType::COM_STMT_EXECUTE);


        ///Users/yuyi/Downloads/mysql-5.7.19/libmysql/libmysql.c 2146
        ///Users/yuyi/Downloads/mysql-5.7.19/include/byte_order_generic.h int4store
        /**
        int4store(buff, stmt->stmt_id);
        buff[4]= (char) stmt->flags;
        int4store(buff+5, 1);
         */
        //4字节预处理语句的ID值
        $data = pack("V", $smtid);
        //1字节标志位
        $data .= chr(Cursor::TYPE_NO_CURSOR);
        //4字节保留（值恒为0x01）
        $data .= pack("V", 0x01);


        // mysql源码 绑定参数的实现
        // /Users/yuyi/Downloads/mysql-5.7.19/libmysql/libmysql.c 2901行
        // 函数 mysql_stmt_bind_param

        ///Users/yuyi/Downloads/mysql-5.7.19/libmysql/libmysql.c 2240
        //n字节空位图（Null-Bitmap，长度 = (参数数量 + 7) / 8 字节）
        if ($params_len > 0) {
            $len = intval((count($params) + 7) / 8);
            for ($i = 0; $i < $len; $i++) {
                $data .= chr(0x00);
            }

            //libmysql/libmysql.c 2251
            //是否发送类型到服务器 send_types_to_server
            //1字节参数分隔标志
            $data .= chr(0x01);

            /*
             Users/yuyi/Downloads/mysql-5.7.19/libmysql/libmysql.c 1916
             uint typecode= param->buffer_type | (param->is_unsigned ? 32768 : 0);
             int2store(*pos, typecode);
             *pos+= 2;
             */

            $pfields = FieldType::parse($params);

            //n字节每个参数的类型值（长度 = 参数数量 * 2 字节）
            foreach ($pfields as $value) {
                //$type = FieldType::VAR_STRING;
    //            if (is_numeric($value)) {
    //                if (intval($value) == $value) {
    //                    $type = FieldType::LONG|0;
    //                } else {
    //                    $type = FieldType::DOUBLE|0;
    //                }
    //            } else {
    //                $type = FieldType::VAR_STRING;
    //            }

                $data .= pack("v", $value->type);
            }

            //libmysql/libmysql.c 2081
            //n字节每个参数的值
            foreach ($pfields as $value) {
                $data .= $value->pack();//Packet::storeLength(strlen($value)).$value;
            }
        }

        //封包
       // $data = pack('L', strlen($data)).$data;
        //(new Packet($data))->debugDump();


        //Net::send($data);
		$success = Packet::writeCommand(CommandType::COM_STMT_EXECUTE, $data);
		if (!$success) {
			echo "write command COM_STMT_EXECUTE failure\r\n";
			return false;
		}

        //列数量
        $res = Net::readPacket();
        $packet = new  Packet($res);
		$packet->debugDump();

        $columns_count = $packet->readUint8();
        //var_dump($columns_count);

        //响应列包
        //列信息
        $columns = [];
        $cc = 0;
        //一直读取直到遇到结束报文
        while ($cc < $columns_count) {
            $cc++;
            $res        = Net::readPacket();
            $packet     = new Packet($res);
            $columns[]  = $packet->getColumns();
            unset($packet);
        }
        //var_dump($columns);

        //EOF
        $res = Net::readPacket();
        $packet = new Packet($res);
        $packet->debugDump();

        /**
         *4	预处理语句的ID值（小字节序）
          4	数据的行数（小字节序）
         */
        $rows    = 1;
        $packet  = chr($smtid).chr($smtid >> 8) .chr($smtid >> 16) .chr($smtid >> 24);
        $packet .= chr($rows).chr($rows >> 8) .chr($rows >> 16) .chr($rows >> 24);

        $success = Packet::writeCommand(CommandType::COM_STMT_FETCH, $packet);
		if (!$success) {
			echo "write command COM_STMT_FETCH failure\r\n";
			return false;
		}

        //行信息
        $rows = [];
        //一直读取直到遇到结束报文
        while (1)
        {
            $res = Net::readPacket();
            //var_dump($res);
            if (ord($res[0]) == Packet::EOF_HEAD) {
                break;
            }
            $index = 0;
            $row   = [];

            $packet = new Packet($res);
            //$packet->debugDump();
           // exit;
//            for ($i=0;$i<64;$i++){
//                echo ord($res[$i]),"-";
//            }
//            echo "\r\n";
           // $packet->read(1);
            //
            //1	结构头（0x00）
           /// (列数量 + 7 + 2) / 8	空位图
          //  n	字段值
          //   * https://dev.mysql.com/doc/internals/en/myisam-column-attributes.html
             //
            $packet->read(intval(($columns_count+9)/8)+1);

            while($index < $columns_count) {
                $type = $columns[$index]["type"];
                $name = $columns[$index]["column"];

                $field_is_unsigned = $columns[$index]["flag"] & FieldFlag::UNSIGNED_FLAG;


                switch ($type) {
                    case FieldType::DECIMAL:// 	= 0x00;
                        break;
                    case FieldType::TINY:// 		= 0x01;
                        $row[$name] = $packet->readUint8();
                        break;
                    case FieldType::SHORT;// 		= 0x02;
                        $row[$name] = $packet->readUint16();
                        break;
                    case FieldType::LONG:// 		= 0x03;
                        $row[$name] = $packet->readUint32();
                        break;
                    case FieldType::FLOAT://		= 0x04;
                        //mysql-5.7.19/include/big_endian.h
                        break;
                    case FieldType::DOUBLE:// 		= 0x05;
                        break;
                    case FieldType::NULL:// 		= 0x06;
                        break;
                    case FieldType::TIMESTAMP://	= 0x07;
                        break;
                    case FieldType::BIGINT://0x08;
                        //8bytes
                        $row[$name] = $packet->readUint64();
                        break;
                    case FieldType::INT24:// 		= 0x09;
                        $row[$name] = $packet->readUint24();
                        break;
                    case FieldType::DATE:// 		= 0x0A;
                        break;
                    case FieldType::TIME:// 		= 0x0B;
                    break;
                    case FieldType::DATETIME://	= 0x0C;
                        //8bytes
                        $row[$name] = $packet->readDatetime();
                        break;
                    case FieldType::YEAR:// 		= 0x0D;
                        break;
                    case FieldType::NEWDATE:// 	= 0x0E;
                        break;
                    case FieldType::VARCHAR:// 	= 0x0F;// (new in MySQL 5.0)
                        break;
                    case FieldType::BIT:// 		= 0x10;// (new in MySQL 5.0)
                        break;
                    case FieldType::TIMESTAMP2:// 	= 0x11;//17;
                        break;
                    case FieldType::DATETIME2:// 	= 0x12;//18;
                        break;
                    case FieldType::TIME2:// 		= 0x13;//19;
                        break;
                    case FieldType::NEWDECIMAL:// 	= 0xF6;// (new in MYSQL 5.0)
                        break;
                    case FieldType::ENUM:// 		= 0xF7;
                        break;
                    case FieldType::SET:// 		= 0xF8;
                        break;
                    case FieldType::TINY_BLOB:// 	= 0xF9;
                        break;
                    case FieldType::MEDIUM_BLOB:// = 0xFA;
                        break;
                    case FieldType::LONG_BLOB:// 	= 0xFB;

                        break;
                    case FieldType::BLOB:// 		= 0xFC;
                        //length coded binary
                        $row[$name] = $packet->next();
                        break;
                    case FieldType::VAR_STRING:// 	= 0xFD; //253
                        $row[$name] = $packet->next();
                        break;
                    case FieldType::STRING:// 		= 0xFE;
                        break;
                    case FieldType::GEOMETRY:// 	= 0xFF;
                }
               //  = $packet->next();
                $index++;
            }
            unset($packet, $res);
            $rows[] = $row;
        }

        var_dump($rows);

		//mysql-server/sql/protocol_classic.cc 904
		//mysql-server/libmysql/libmysql.c 4819
		//COM_CLOSE_STMT 释放预处理资源
        //COM_STMT_FETCH 之后会自动 close_stmt
		/*$packet  = chr($smtid).chr($smtid >> 8) .chr($smtid >> 16) .chr($smtid >> 24);
		$success = Packet::writeCommand(CommandType::COM_STMT_CLOSE, $packet);
		if (!$success) {
			echo "write command COM_STMT_CLOSE failure\r\n";
			return false;
		}

		$res = Net::readPacket();
		(new Packet($res))->debugDump();

		//}
		//exit;
        */
        return $rows;
        //这里得到响应结果
        //var_dump($res);

        //return $res;
    }

    /**
     * @return bool
     */
    public static function close_stmt($smtid)
    {
        //mysql-server/sql/protocol_classic.cc 904
		//mysql-server/libmysql/libmysql.c 4819
		//COM_CLOSE_STMT 释放预处理资源
        //COM_STMT_FETCH 之后会自动 close_stmt
		$packet  = chr($smtid).chr($smtid >> 8) .chr($smtid >> 16) .chr($smtid >> 24);
		$success = Packet::writeCommand(CommandType::COM_STMT_CLOSE, $packet);
		if (!$success) {
			echo "write command COM_STMT_CLOSE failure\r\n";
			return false;
		}

		$res = Net::readPacket();
		(new Packet($res))->debugDump();
		return true;
    }

}