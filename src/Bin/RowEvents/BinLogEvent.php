<?php namespace Wing\Bin\RowEvents;
use Wing\Bin\BinLogPack;
use Wing\Bin\Constant\Column;
use Wing\Bin\Packet;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 06:58
 */
abstract class BinlogEvent {

	protected $packet;
	protected $event_type;
	protected $packet_size;
	//private $table_id;

    public static $EVENT_TYPE;

    //public static $TABLE_ID;
    public static $TABLE_NAME;

    public static $SCHEMA_NAME;

    public static $TABLE_MAP;
    /**
     * @var BinLogPack $PACK
     */
//    public static $PACK;
    public static $PACK_SIZE;
    public static $FLAGS;
    public static $EXTRA_DATA_LENGTH;
    public static $EXTRA_DATA;
    public static $SCHEMA_LENGTH;
    public static $COLUMNS_NUM;


	public static $EVENT_INFO;
	//public static $EVENT_TYPE;

	private static $_PACK_KEY = 0;
	private static $_PACK;
	private static $_BUFFER = '';

	private static $_instance = null;

	// 持久化记录 file pos  不能在next event 为dml操作记录
	// 获取不到table map
	private static $_FILE_NAME;
	private static $_POS;

    public static $bitCountInByte = [
        0, 1, 1, 2, 1, 2, 2, 3, 1, 2, 2, 3, 2, 3, 3, 4,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        1, 2, 2, 3, 2, 3, 3, 4, 2, 3, 3, 4, 3, 4, 4, 5,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        2, 3, 3, 4, 3, 4, 4, 5, 3, 4, 4, 5, 4, 5, 5, 6,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        3, 4, 4, 5, 4, 5, 5, 6, 4, 5, 5, 6, 5, 6, 6, 7,
        4, 5, 5, 6, 5, 6, 6, 7, 5, 6, 6, 7, 6, 7, 7, 8,
    ];

    public function __construct(Packet $packet,$event_type, $size = 0) {
		$this->packet = $packet;
        $this->event_type  = $event_type;
        self::$PACK_SIZE   = $size;
        $this->packet_size = $size;
    }

    public function readTableId()
    {
        $a = (int)(ord($this->packet->read(1)) & 0xFF);
        $a += (int)((ord($this->packet->read(1)) & 0xFF) << 8);
        $a += (int)((ord($this->packet->read(1)) & 0xFF) << 16);
        $a += (int)((ord($this->packet->read(1)) & 0xFF) << 24);
        $a += (int)((ord($this->packet->read(1)) & 0xFF) << 32);
        $a += (int)((ord($this->packet->read(1)) & 0xFF) << 40);
        return $a;
    }

    public static function bitCount($bitmap) {
        $n = 0;
        for($i=0;$i<strlen($bitmap);$i++) {
            $bit = $bitmap[$i];
            if(is_string($bit)) {
                $bit = ord($bit);
            }
            $n += self::$bitCountInByte[$bit];
        }
        return $n;
    }



	public static $unget = [];
	public function read($length) {
		$length = (int)$length;
		$n='';

		if(self::$_BUFFER) {
			$n = substr(self::$_BUFFER, 0 , $length);
			if(strlen($n) == $length) {
				self::$_BUFFER = substr(self::$_BUFFER, $length);;
				return $n;
			} else {
				self::$_BUFFER = '';
				$length = $length - strlen($n);
			}

		}

		for($i = self::$_PACK_KEY; $i < self::$_PACK_KEY + $length; $i++) {
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
	 * Length coded numbers can be anywhere from 1 to 9 bytes depending
	 * on the value of the first byte.
	 * From PyMYSQL source code
	 * @return int|string
	 */
	public function readCodedBinary(){
		$flag = ord($this->packet->read(1));

		if($flag == Column::NULL) {
			return '';
		}

		else if ($flag < Column::UNSIGNED_CHAR) {
			return $flag;
		}

		else if ($flag == Column::UNSIGNED_SHORT) {
			return $this->unpackUint16($this->packet->read(Column::UNSIGNED_SHORT_LENGTH));
		}

		else if ($flag == Column::UNSIGNED_INT24) {
			return $this->unpackInt24($this->read(Column::UNSIGNED_INT24_LENGTH));
		}

		else if ($flag == Column::UNSIGNED_INT64) {
			return $this->unpackInt64($this->read(Column::UNSIGNED_INT64_LENGTH));
		}

		return null;
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

		return null;

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

	public function unread($data) {
		self::$_BUFFER.=$data;
	}

	abstract public function Parse();

	protected function columnFormat($cols_bitmap)
	{
		$values = [];
		$len    = (int)((self::bitCount($cols_bitmap) + 7) / 8);

		//null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
		//See http://dev.mysql.com/doc/internals/en/rows-event.html

		$null_bitmap 	   = $this->packet->read($len);
		$null_bitmap_index = 0;
		$fields            = self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'];

		foreach ($fields as $i => $value) {
			$column   = $value;
			$name     = $value['name'];
			$unsigned = $value['unsigned'];


			if (self::BitGet($cols_bitmap, $i) == 0) {
				$values[$name] = null;
				continue;
			}

			if (self::_is_null($null_bitmap, $null_bitmap_index)) {
				$values[$name] = null;
			} elseif ($column['type'] == FieldType::TINY) {
				if ($unsigned)
					$values[$name] = unpack("C", self::$PACK->read(1))[1];
				else
					$values[$name] = unpack("c", self::$PACK->read(1))[1];
			} elseif ($column['type'] == FieldType::SHORT) {
				if ($unsigned)
					$values[$name] = unpack("S", self::$PACK->read(2))[1];
				else
					$values[$name] = unpack("s", self::$PACK->read(2))[1];
			} elseif ($column['type'] == FieldType::LONG) {

				if ($unsigned) {
					$values[$name] = unpack("I", self::$PACK->read(4))[1];
				} else {
					$values[$name] = unpack("i", self::$PACK->read(4))[1];
				}
			} elseif ($column['type'] == FieldType::INT24) {
				if ($unsigned)
					$values[$name] = self::$PACK->readUint24();
				else
					$values[$name] = self::$PACK->read_int24();
			} elseif ($column['type'] == FieldType::FLOAT)
				$values[$name] = unpack("f", self::$PACK->read(4))[1];
			elseif ($column['type'] == FieldType::DOUBLE)
				$values[$name] = unpack("d", self::$PACK->read(8))[1];
			elseif ($column['type'] == FieldType::VARCHAR ||
				$column['type'] == FieldType::STRING
			) {
				if ($column['max_length'] > 255)
					$values[$name] = self::_read_string(2, $column);
				else
					$values[$name] = self::_read_string(1, $column);
			} elseif ($column['type'] == FieldType::NEWDECIMAL) {

				//$precision = unpack('C', self::$PACK->read(1))[1];
				//$decimals  = unpack('C', self::$PACK->read(1))[1];

//var_dump($precision,$decimals);exit;
//precision = metadata[:precision]
//        scale = metadata[:decimals]
				$values[$name] = self::read_new_decimal($column);
			} elseif ($column['type'] == FieldType::BLOB) {
				//ok
				$values[$name] = self::_read_string($column['length_size'], $column);

			}
			elseif ($column['type'] == FieldType::DATETIME) {

				$values[$name] = self::_read_datetime();
			} elseif ($column['type'] == FieldType::DATETIME2) {
				//ok
				$values[$name] = self::_read_datetime2($column);
			}elseif ($column['type'] == FieldType::TIME2) {

				$values[$name] = self::_read_time2($column);
			}
			elseif ($column['type'] == FieldType::TIMESTAMP2){
				//ok
				$time = date('Y-m-d H:i:m',self::$PACK->read_int_be_by_size(4));
				// 微妙
				$time .= '.' . self::_add_fsp_to_time($column);
				$values[$name] = $time;
			}
			elseif ($column['type'] == FieldType::DATE)
				$values[$name] = self::_read_date();
			/*
		elseif ($column['type'] == FieldType::TIME:
			$values[$name] = self.__read_time()
		elseif ($column['type'] == FieldType::DATE:
			$values[$name] = self.__read_date()
			*/
			elseif ($column['type'] == FieldType::TIMESTAMP) {
				$values[$name] = date('Y-m-d H:i:s', self::$PACK->readUint32());
			}

			# For new date format:
			/*
						elseif ($column['type'] == FieldType::TIME2:
							$values[$name] = self.__read_time2(column)
						elseif ($column['type'] == FieldType::TIMESTAMP2:
							$values[$name] = self.__add_fsp_to_time(
									datetime.datetime.fromtimestamp(
										self::$PACK->read_int_be_by_size(4)), column)
						*/
			elseif ($column['type'] == FieldType::LONGLONG) {
				if ($unsigned) {
					$values[$name] = self::$PACK->readUint64();
				} else {
					$values[$name] = self::$PACK->readInt64();
				}

			} elseif($column['type'] == FieldType::ENUM) {
				$values[$name] = $column['enum_values'][self::$PACK->read_uint_by_size($column['size']) - 1];
			} else {
			}
			/*
			elseif ($column['type'] == FieldType::YEAR:
				$values[$name] = self::$PACK->read_uint8() + 1900
			elseif ($column['type'] == FieldType::SET:
				# We read set columns as a bitmap telling us which options
				# are enabled
				bit_mask = self::$PACK->read_uint_by_size(column.size)
				$values[$name] = set(
					val for idx, val in enumerate(column.set_values)
				if bit_mask & 2 ** idx
				) or None

			elseif ($column['type'] == FieldType::BIT:
				$values[$name] = self.__read_bit(column)
			elseif ($column['type'] == FieldType::GEOMETRY:
				$values[$name] = self::$PACK->read_length_coded_pascal_string(
						column.length_size)
			else:
				raise NotImplementedError("Unknown MySQL column type: %d" %
					(column.type))
			*/
			$null_bitmap_index += 1;
		}
		//$values['table_name'] = self::$TABLE_NAME;
		return $values;
	}


}