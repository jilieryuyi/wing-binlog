<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:14
 */
class BinLogPack {


    public static $EVENT_INFO;
    public static $EVENT_TYPE;

    private static $_PACK_KEY = 0;
    private static $_PACK;

    private static $_instance = null;

    // 持久化记录 file pos  不能在next event 为dml操作记录
    // 获取不到table map
    private static $_FILE_NAME;
    private static $_POS;


    public function getLastBinLogFile()
    {
        return self::$_FILE_NAME;
    }
    public function getLastPos()
    {
        return self::$_POS;
    }
    public static function getInstance() {
        if(!self::$_instance) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }


    public function init($pack, $checkSum = true) {
var_dump($pack);
        if(!self::$_instance) {
            self::$_instance = new self();
        }

        //package error
        if (strlen($pack) < 19) return null;
        //
        self::$_PACK       = $pack;
        self::$_PACK_KEY   = 0;
        self::$EVENT_INFO  = [];
        $this->advance(1);
        self::$EVENT_INFO['time'] = $timestamp  = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['type'] = self::$EVENT_TYPE = unpack('C', $this->read(1))[1];
        self::$EVENT_INFO['id']   = $server_id  = unpack('L', $this->read(4))[1];
        self::$EVENT_INFO['size'] = $event_size = unpack('L', $this->read(4))[1];

        //position of the next event
        self::$EVENT_INFO['pos']  = $log_pos    = unpack('L', $this->read(4))[1];//
        self::$EVENT_INFO['flag'] = $flags      = unpack('S', $this->read(2))[1];
        $event_size_without_header = $checkSum === true ? ($event_size -23) : $event_size - 19;
        $data = [];



        switch (self::$EVENT_TYPE) {
			// 映射fileds相关信息
			case MysqlEventType::TABLE_MAP_EVENT: {
				RowEvent::tableMap(self::getInstance(), self::$EVENT_TYPE);
			}
			break;
			case MysqlEventType::UPDATE_ROWS_EVENT_V2:
			case MysqlEventType::UPDATE_ROWS_EVENT_V1: {
				$data = RowEvent::updateRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
				self::$_POS = self::$EVENT_INFO['pos'];
			}
			break;
			case MysqlEventType::WRITE_ROWS_EVENT_V1:
			case MysqlEventType::WRITE_ROWS_EVENT_V2: {
				$data = RowEvent::addRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
				self::$_POS = self::$EVENT_INFO['pos'];
			}
			break;
			case MysqlEventType::DELETE_ROWS_EVENT_V1:
			case MysqlEventType::DELETE_ROWS_EVENT_V2: {
				$data = RowEvent::delRow(self::getInstance(), self::$EVENT_TYPE, $event_size_without_header);
				self::$_POS = self::$EVENT_INFO['pos'];
			}
			break;
			case MysqlEventType::ROTATE_EVENT: {
				$log_pos = $this->readUint64();
            	self::$_FILE_NAME = $this->read($event_size_without_header - 8);
        	}
        	break;
        	case MysqlEventType::HEARTBEAT_LOG_EVENT: {
				//心跳检测机制
				$binlog_name = $this->read($event_size_without_header);
            	echo '心跳事件 ' . $binlog_name . "\n";
        	}
        	break;
			case MysqlEventType::QUERY_EVENT:
				var_dump(self::$EVENT_INFO);
				echo "查询事件";
				$this->read(16);
				$binlog_name = $this->read($event_size_without_header);
				var_dump($binlog_name);
				break;
			default:
				echo "未知事件";
				var_dump(self::$EVENT_TYPE);
				break;
        }

        if (WING_DEBUG) {
            $msg  = self::$_FILE_NAME;
            $msg .= '-- next pos -> '.$log_pos;
            $msg .= ' --  typeEvent -> '.self::$EVENT_TYPE;
            wing_log("slave_debug", $msg);
        }
		wing_log("slave_bin", $pack."\r\n\r\n");
        return $data;
    }

    public static $unget = [];
    public function read($length) {
        $length = (int)$length;
        $n='';

//        if (count(self::$unget) > 0) {
//        	foreach (self::$unget as $kk => $vv) {
//				self::$_PACK=$vv.self::$_PACK;//array_unshift(self::$_PACK, $vv);
//				unset(self::$unget[$kk]);
//                self::$_PACK_KEY--;
//			}
//		}

        for($i = self::$_PACK_KEY; $i < self::$_PACK_KEY + $length; $i++) {
            if (!isset(self::$_PACK[$i])) return $n;
            $n .= self::$_PACK[$i];
        }

        self::$_PACK_KEY += $length;

        return $n;

    }

    /**
     * @brief 前进步长
     * @param $length
     */
    public  function advance($length) {
        $this->read($length);
    }

    /**
     * @brief read a 'Length Coded Binary' number from the data buffer.
     ** Length coded numbers can be anywhere from 1 to 9 bytes depending
     ** on the value of the first byte.
     ** From PyMYSQL source code
     * @return int|string
     */

    public function readCodedBinary(){
        $c = ord($this->read(1));
        if($c == ConstMy::NULL_COLUMN) {
            return '';
        }
        if($c < ConstMy::UNSIGNED_CHAR_COLUMN) {
            return $c;
        } elseif($c == ConstMy::UNSIGNED_SHORT_COLUMN) {
            return $this->unpackUint16($this->read(ConstMy::UNSIGNED_SHORT_LENGTH));

        }elseif($c == ConstMy::UNSIGNED_INT24_COLUMN) {
            return $this->unpackInt24($this->read(ConstMy::UNSIGNED_INT24_LENGTH));
        }
        elseif($c == ConstMy::UNSIGNED_INT64_COLUMN) {
            return $this->unpackInt64($this->read(ConstMy::UNSIGNED_INT64_LENGTH));
        }
    }

    public function unpackUint16($data) {
        return unpack("S",$data[0] . $data[1])[1];
    }

    public function unpackInt24($data) {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        return $a;
    }

    //ok
    public function unpackInt64($data) {
        $a = (int)(ord($data[0]) & 0xFF);
        $a += (int)((ord($data[1]) & 0xFF) << 8);
        $a += (int)((ord($data[2]) & 0xFF) << 16);
        $a += (int)((ord($data[3]) & 0xFF) << 24);
        $a += (int)((ord($data[4]) & 0xFF) << 32);
        $a += (int)((ord($data[5]) & 0xFF) << 40);
        $a += (int)((ord($data[6]) & 0xFF) << 48);
        $a += (int)((ord($data[7]) & 0xFF) << 56);
        return $a;
    }

    public function read_int24()
    {
        $data = unpack("CCC", $this->read(3));

        $res = $data[1] | ($data[2] << 8) | ($data[3] << 16);
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    public function read_int24_be()
    {
        $data = unpack('C3', $this->read(3));
        $res = ($data[1] << 16) | ($data[2] << 8) | $data[3];
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    public function read_int32_be()
    {
        $data = unpack('C4', $this->read(4));
        $res = ($data[1] << 24)|($data[2] << 16) | ($data[3] << 8) | $data[4];
        if ($res >= 0x800000)
            $res -= 0x1000000;
        return $res;
    }

    //
    public function readUint8()
    {
        return unpack('C', $this->read(1))[1];
    }

    //
    public function readUint16()
    {
        return unpack('S', $this->read(2))[1];
    }

    public function readUint24()
    {
        $data = unpack("C3", $this->read(3));
        return $data[1] + ($data[2] << 8) + ($data[3] << 16);
    }

    //
    public function readUint32()
    {
        return unpack('I', $this->read(4))[1];
    }

    public function readUint40()
    {
        $data = unpack("CI", $this->read(5));
        return $data[1] + ($data[2] << 8);
    }

    public function read_int40_be()
    {
        $data1= unpack("N", $this->read(4))[1];
        $data2 = unpack("C", $this->read(1))[1];
        return $data2 + ($data1 << 8);
    }

    //
    public function readUint48()
    {
        $data = unpack("vvv", $this->read(6));
        return $data[1] + ($data[2] << 16) + ($data[3] << 32);
    }

    //
    public function readUint56()
    {
        $data = unpack("CSI", $this->read(7));
        return $data[1] + ($data[2] << 8) + ($data[3] << 24);
    }

    /*
     * 不支持unsigned long long，溢出
     */
    public function readUint64() {
        $d = $this->read(8);
        $data = unpack('V*', $d);
        $bigInt = bcadd($data[1], bcmul($data[2], bcpow(2, 32)));
        return $bigInt;

//        $unpackArr = unpack('I2', $d);
        //$data = unpack("C*", $d);
        //$r = $data[1] + ($data[2] << 8) + ($data[3] << 16) + ($data[4] << 24);//+
        //$r2= ($data[5]) + ($data[6] << 8) + ($data[7] << 16) + ($data[8] << 24);

//        return $unpackArr[1] + ($unpackArr[2] << 32);
    }

    public function readInt64()
    {
        return $this->readUint64();
    }

    public function read_uint_by_size($size)
    {

        if($size == 1)
            return $this->readUint8();
        elseif($size == 2)
            return $this->readUint16();
        elseif($size == 3)
            return $this->readUint24();
        elseif($size == 4)
            return $this->readUint32();
        elseif($size == 5)
            return $this->readUint40();
        elseif($size == 6)
            return $this->readUint48();
        elseif($size == 7)
            return $this->readUint56();
        elseif($size == 8)
            return $this->readUint64();
    }
    public function read_length_coded_pascal_string($size)
    {
        $length = $this->read_uint_by_size($size);
        return $this->read($length);
    }

    public function read_int_be_by_size($size) {
        //Read a big endian integer values based on byte number
        if ($size == 1)
            return unpack('c', $this->read($size))[1];
        elseif( $size == 2)
            return unpack('n', $this->read($size))[1];
        elseif( $size == 3)
            return $this->read_int24_be();
        elseif( $size == 4)
            return unpack('N', $this->read($size))[1];
        elseif( $size == 5)
            return $this->read_int40_be();
        //TODO
        elseif( $size == 8)
            return unpack('N', $this->read($size))[1];

return null;//
        if ($size == 1)
            return unpack('b', $this->read($size))[1];
        elseif ($size == 2)
            return unpack('h', $this->read($size))[1];
        elseif($size == 3)
            return $this->read_int24_be();
        elseif($size == 4)
            return unpack('i', $this->read($size))[1];
        elseif($size == 5)
            return $this->read_int40_be();
        elseif($size == 8)
            return unpack('l', $this->read($size))[1];

    }

    /**
     * @return bool
     */
    public function isComplete($size) {
        // 20解析server_id ...
        if(self::$_PACK_KEY + 1 - 20 < $size) {
            return false;
        }
        return true;
    }

    /**
     * @biref  初始化设置 file，pos，解决持久化file为空问题
     * @param $file
     * @param $pos
     */
    public static function setFilePos($file, $pos) {
        self::$_FILE_NAME = $file;
        self::$_POS       = $pos;
    }

    /**
     * @brief 获取binlog file，pos持久化
     * @return array
     */
    public static function getFilePos() {
        return array(self::$_FILE_NAME, self::$_POS);
    }

}