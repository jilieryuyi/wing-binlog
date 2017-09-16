<?php namespace Wing\Bin;
use Wing\Bin\Constant\CommandType;
use Wing\Bin\Constant\Cursor;
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

	public static function query($sql)
    {
		$packet = Packet::query($sql);
		Net::send($packet);

		$res   = Net::readPacket();
		$fbyte = ord($res[0]);

		//这里可能是三种类型的报文 Result set、Field和Row Data
        //以下解析 Result set
		if ($fbyte >= Packet::RESULT_SET_HEAD[0] && $fbyte <= Packet::RESULT_SET_HEAD[1]) {
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
            $server_status = $packet->readUint16();

            var_dump(self::$rows_affected,self::$last_insert_id, $server_status);

            return true;
        }

        else if ($fbyte == Packet::ERR_PACK_HEAD) {
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

    public static function excute($sql,array $params = null)
    {
        $chunk_size = strlen($sql) + 1;
        $prelude    = pack('LC',$chunk_size, CommandType::COM_STMT_PREPARE);
        Net::send($prelude . $sql);
        $res = Net::readPacket();

        $smtid = unpack("L", $res[1].$res[2].$res[3].chr(0))[1];
        echo "smtid=",$smtid,"\r\n";

        //cloumns count
        echo "cloumns count=".unpack("n", $res[4].$res[5].$res[6].$res[7])[1],"\r\n";



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
        $data  = pack('C', CommandType::COM_STMT_EXECUTE);
        //4字节预处理语句的ID值
        $data .= pack("V", $smtid);
        $data .= Cursor::TYPE_NO_CURSOR;
        $data .= pack("V", 0x01);

        $len = intval((count($params)+7)/8);
        for ($i=0;$i<$len;$i++)
            $data.=chr(0x00);

        $data .= chr(0x01);
        for ($i=0;$i<count($params);$i++)
        $data .= pack("v", FieldType::TINY);

        $data = pack("L", strlen($data)).$data;


        Net::send($data );
        $res = Net::readPacket();
        //Packet::success($res);
        var_dump($res);


       // $chunk_size = strlen($sql) + 1;
        $prelude = pack('LC',1, CommandType::COM_STMT_FETCH);
        Net::send($prelude);
        $res = Net::readPacket();
        return $res;
    }

}