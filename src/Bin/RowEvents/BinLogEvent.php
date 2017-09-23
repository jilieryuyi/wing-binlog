<?php namespace Wing\Bin\RowEvents;
use Wing\Bin\BinLogPack;
use Wing\Bin\Constant\Column;
use Wing\Bin\FieldType;
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
//	private static $_PACK;
	private static $_BUFFER = '';


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
        $this->packet_SIZE   = $size;
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



//	public static $unget = [];
//	public function read($length) {
//		$length = (int)$length;
//		$n='';
//
//		if(self::$_BUFFER) {
//			$n = substr(self::$_BUFFER, 0 , $length);
//			if(strlen($n) == $length) {
//				self::$_BUFFER = substr(self::$_BUFFER, $length);;
//				return $n;
//			} else {
//				self::$_BUFFER = '';
//				$length = $length - strlen($n);
//			}
//
//		}
//
//		for($i = self::$_PACK_KEY; $i < self::$_PACK_KEY + $length; $i++) {
//			$n .= self::$_PACK[$i];
//		}
//
//		self::$_PACK_KEY += $length;
//
//		return $n;
//
//	}

	/**
	 * @brief 前进步长
	 * @param $length
	 */
//	public  function advance($length) {
//		$this->read($length);
//	}

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
			return $this->unpackInt24($this->packet->read(Column::UNSIGNED_INT24_LENGTH));
		}

		else if ($flag == Column::UNSIGNED_INT64) {
			return $this->unpackInt64($this->packet->read(Column::UNSIGNED_INT64_LENGTH));
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





//	public function read_int32_be()
//	{
//		$data = unpack('C4', $this->packet->read(4));
//		$res = ($data[1] << 24)|($data[2] << 16) | ($data[3] << 8) | $data[4];
//		if ($res >= 0x800000)
//			$res -= 0x1000000;
//		return $res;
//	}

	//
//	public function readUint8()
//	{
//		return unpack('C', $this->packet->read(1))[1];
//	}

	//
//	public function readUint16()
//	{
//		return unpack('S', $this->read(2))[1];
//	}
//
//	public function readUint24()
//	{
//		$data = unpack("C3", $this->read(3));
//		return $data[1] + ($data[2] << 8) + ($data[3] << 16);
//	}
//
//	//
//	public function readUint32()
//	{
//		return unpack('I', $this->read(4))[1];
//	}


	/**
	 * @return bool
	 */
//	public function isComplete($size) {
//		// 20解析server_id ...
//		if(self::$_PACK_KEY + 1 - 20 < $size) {
//			return false;
//		}
//		return true;
//	}

	/**
	 * @biref  初始化设置 file，pos，解决持久化file为空问题
	 * @param $file
	 * @param $pos
	 */
//	public function setFilePos($file, $pos) {
//		self::$_FILE_NAME = $file;
//		self::$_POS       = $pos;
//	}

	/**
	 * @brief 获取binlog file，pos持久化
	 * @return array
	 */
//	public function getFilePos() {
//		return array(self::$_FILE_NAME, self::$_POS);
//	}

	/**
	 * @return void
	 */
//	public function unread($data) {
//		self::$_BUFFER.=$data;
//	}

	/**
	 * @return array
	 */
	abstract public function Parse();
	public function BitGet($bitmap, $position)
	{
		$bit = $bitmap[intval($position / 8)];

		if (is_string($bit)) {

			$bit = ord($bit);
		}

		return $bit & (1 << ($position & 7));
	}
	public function _is_null($null_bitmap, $position)
	{
		$bit = $null_bitmap[intval($position / 8)];
		if (is_string($bit)) {
			$bit = ord($bit);
		}


		return $bit & (1 << ($position % 8));
	}

	private function _read_string($size, $column)
	{
		$string = $this->packet->read_length_coded_pascal_string($size);
		if ($column['character_set_name']) {
			//string = string . decode(column . character_set_name)
		}
		return $string;
	}

	private function read_new_decimal($column) {
		#Read MySQL's new decimal format introduced in MySQL 5"""

		# This project was a great source of inspiration for
		# understanding this storage format.
		# https://github.com/jeremycole/mysql_binlog

		$digits_per_integer = 9;
		$compressed_bytes = [0, 1, 1, 2, 2, 3, 3, 4, 4, 4];
		$integral = ($column['precision'] - $column['decimals']);
		$uncomp_integral = intval($integral / $digits_per_integer);
		$uncomp_fractional = intval($column['decimals'] / $digits_per_integer);
		$comp_integral = $integral - ($uncomp_integral * $digits_per_integer);
		$comp_fractional = $column['decimals'] - ($uncomp_fractional * $digits_per_integer);

		# Support negative
		# The sign is encoded in the high bit of the the byte
		# But this bit can also be used in the value
		$value = $this->packet->readUint8();
		if ( ($value & 0x80) != 0) {
			$res  = "";
			$mask = 0;
		}else {
			$mask = -1;
			$res  = "-";
		}
		$this->packet->unread(pack('C', $value ^ 0x80));
		$size = $compressed_bytes[$comp_integral];
		if ($size > 0) {
			$value =  $this->packet->read_int_be_by_size($size) ^ $mask;
			$res .= (string)$value;
		}


		for($i=0;$i<$uncomp_integral;$i++) {
			$value = unpack('N', $this->packet->read(4))[1] ^ $mask;
			$res .= sprintf('%09d' , $value);
		}

		$res .= ".";
		for($i=0;$i<$uncomp_fractional;$i++) {
			$value = unpack('N', $this->packet->read(4))[1] ^ $mask;
			$res .= sprintf('%09d' , $value);
		}

		$size = $compressed_bytes[$comp_fractional];
		if ($size > 0) {
			$value = $this->packet->read_int_be_by_size($size) ^ $mask;

			$res.=sprintf('%0'.$comp_fractional.'d' , $value);
		}
		return number_format($res,$comp_fractional,'.','');
	}


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


			if ($this->BitGet($cols_bitmap, $i) == 0) {
				$values[$name] = null;
				continue;
			}

			if (self::_is_null($null_bitmap, $null_bitmap_index)) {
				$values[$name] = null;
			} elseif ($column['type'] == FieldType::TINY) {
				if ($unsigned)
					$values[$name] = unpack("C", $this->packet->read(1))[1];
				else
					$values[$name] = unpack("c", $this->packet->read(1))[1];
			} elseif ($column['type'] == FieldType::SHORT) {
				if ($unsigned)
					$values[$name] = unpack("S", $this->packet->read(2))[1];
				else
					$values[$name] = unpack("s", $this->packet->read(2))[1];
			} elseif ($column['type'] == FieldType::LONG) {

				if ($unsigned) {
					$values[$name] = unpack("I", $this->packet->read(4))[1];
				} else {
					$values[$name] = unpack("i", $this->packet->read(4))[1];
				}
			} elseif ($column['type'] == FieldType::INT24) {
				if ($unsigned)
					$values[$name] = $this->packet->readUint24();
				else
					$values[$name] = $this->packet->read_int24();
			} elseif ($column['type'] == FieldType::FLOAT)
				$values[$name] = unpack("f", $this->packet->read(4))[1];
			elseif ($column['type'] == FieldType::DOUBLE)
				$values[$name] = unpack("d", $this->packet->read(8))[1];
			elseif ($column['type'] == FieldType::VARCHAR ||
				$column['type'] == FieldType::STRING
			) {
				if ($column['max_length'] > 255)
					$values[$name] = $this->_read_string(2, $column);
				else
					$values[$name] = $this->_read_string(1, $column);
			} elseif ($column['type'] == FieldType::NEWDECIMAL) {

				//$precision = unpack('C', $this->packet->read(1))[1];
				//$decimals  = unpack('C', $this->packet->read(1))[1];

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
				$time = date('Y-m-d H:i:m',$this->packet->read_int_be_by_size(4));
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
				$values[$name] = date('Y-m-d H:i:s', $this->packet->readUint32());
			}

			# For new date format:
			/*
						elseif ($column['type'] == FieldType::TIME2:
							$values[$name] = self.__read_time2(column)
						elseif ($column['type'] == FieldType::TIMESTAMP2:
							$values[$name] = self.__add_fsp_to_time(
									datetime.datetime.fromtimestamp(
										$this->packet->read_int_be_by_size(4)), column)
						*/
			elseif ($column['type'] == FieldType::LONGLONG) {
				if ($unsigned) {
					$values[$name] = $this->packet->readUint64();
				} else {
					$values[$name] = $this->packet->readInt64();
				}

			} elseif($column['type'] == FieldType::ENUM) {
				$values[$name] = $column['enum_values'][$this->packet->read_uint_by_size($column['size']) - 1];
			} else {
			}
			/*
			elseif ($column['type'] == FieldType::YEAR:
				$values[$name] = $this->packet->read_uint8() + 1900
			elseif ($column['type'] == FieldType::SET:
				# We read set columns as a bitmap telling us which options
				# are enabled
				bit_mask = $this->packet->read_uint_by_size(column.size)
				$values[$name] = set(
					val for idx, val in enumerate(column.set_values)
				if bit_mask & 2 ** idx
				) or None

			elseif ($column['type'] == FieldType::BIT:
				$values[$name] = self.__read_bit(column)
			elseif ($column['type'] == FieldType::GEOMETRY:
				$values[$name] = $this->packet->read_length_coded_pascal_string(
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
	private function _read_datetime()
	{
		$value = $this->packet->readUint64();
		if ($value == 0)  # nasty mysql 0000-00-00 dates
			return null;

		$date = $value / 1000000;
		$time = (int)($value % 1000000);

		$year = (int)($date / 10000);
		$month = (int)(($date % 10000) / 100);
		$day = (int)($date % 100);
		if ($year == 0 or $month == 0 or $day == 0)
			return null;

		return $year.'-'.$month.'-'.$day .' '.intval($time / 10000).':'.intval(($time % 10000) / 100).':'.intval($time % 100);

	}

	private function _read_date() {
		$time = $this->packet->readUint24();

		if ($time == 0)  # nasty mysql 0000-00-00 dates
			return null;

		$year = ($time & ((1 << 15) - 1) << 9) >> 9;
		$month = ($time & ((1 << 4) - 1) << 5) >> 5;
		$day = ($time & ((1 << 5) - 1));
		if ($year == 0 || $month == 0 || $day == 0)
			return null;

		return $year.'-'.$month.'-'.$day;
	}

	private function  _read_datetime2($column) {
		/*DATETIME

		1 bit  sign           (1= non-negative, 0= negative)
		17 bits year*13+month  (year 0-9999, month 0-12)
		 5 bits day            (0-31)
		 5 bits hour           (0-23)
		 6 bits minute         (0-59)
		 6 bits second         (0-59)
		---------------------------
		40 bits = 5 bytes
		*/
		$data = $this->packet->read_int_be_by_size(5);

		$year_month = self::_read_binary_slice($data, 1, 17, 40);


		$year=(int)($year_month / 13);
		$month=$year_month % 13;
		$day=self::_read_binary_slice($data, 18, 5, 40);
		$hour=self::_read_binary_slice($data, 23, 5, 40);
		$minute=self::_read_binary_slice($data, 28, 6, 40);
		$second=self::_read_binary_slice($data, 34, 6, 40);
		if($hour < 10) {
			$hour ='0'.$hour;
		}
		if($minute < 10) {
			$minute = '0'.$minute;
		}
		if($second < 10) {
			$second = '0'.$second;
		}
		$time = $year.'-'.$month.'-'.$day.' '.$hour.':'.$minute.':'.$second;
		$microsecond = self::_add_fsp_to_time($column);
		if($microsecond) {
			$time .='.'.$microsecond;
		}
		return $time;
	}

	private function _read_binary_slice($binary, $start, $size, $data_length) {
		/*
		Read a part of binary data and extract a number
		binary: the data
		start: From which bit (1 to X)
		size: How many bits should be read
		data_length: data size
		*/
		$binary = $binary >> $data_length - ($start + $size);
		$mask = ((1 << $size) - 1);
		return $binary & $mask;
	}

	private function _add_fsp_to_time($column)
	{
		/*Read and add the fractional part of time
			For more details about new date format:
			http://dev.mysql.com/doc/internals/en/date-and-time-data-type-representation.html
			*/


		$read = 0;
		$time = '';
		if( $column['fsp'] == 1 or $column['fsp'] == 2)
			$read = 1;
		elseif($column['fsp'] == 3 or $column['fsp'] == 4)
			$read = 2;
		elseif ($column ['fsp'] == 5 or $column['fsp'] == 6)
			$read = 3;
		if ($read > 0) {
			$microsecond = $this->packet->read_int_be_by_size($read);
			if ($column['fsp'] % 2)
				$time = (int)($microsecond / 10);
			else
				$time = $microsecond;
		}
		return $time;
	}




	private function _getUpdateRows($result, $len) {
		$rows = [];
		while(!$this->packet->isComplete($this->packet_size)) {
			$rows[] = [
				"old_data" => $this->columnFormat($result['bitmap1']),
				"new_data" => $this->columnFormat($result['bitmap2'])
			];
		}
		return $rows;
	}

	private function _getDelRows($result, $len) {
		$rows = [];
		while(!$this->packet->isComplete($this->packet_size)) {
			$rows[] = $this->columnFormat($result['bitmap']);
		}
		return $rows;
	}

	private function  _getAddRows($result, $len) {
		$rows = [];

		while(!$this->packet->isComplete($this->packet_size)) {
			$rows[] = $this->columnFormat($result['bitmap']);
		}
		return $rows;
	}
}