<?php namespace Wing\Bin;
use Wing\Bin\Constant\Column;
use Wing\Bin\Constant\EventType;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:14
 */
class BinLogPacket
{
	private $offset = 0;
	private $packet;
	private $buffer = '';

	private static $_instance = null;

	protected $schema_name;
	protected $table_name;
	protected $table_map;


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


	public static function parse($pack, $checkSum = true)
	{
		if(!self::$_instance) {
			self::$_instance = new self();
		}
		return self::$_instance->_parse($pack, $checkSum);
	}

	private function _parse($pack, $checkSum = true) {


		$file_name  = null;
		$data       = [];
		$log_pos    = 0;

		if (strlen($pack) < 19) {
			goto end;
		}

		$this->packet   = $pack;
		$this->offset   = 0;

		$this->advance(1);
		$timestamp  = unpack('L', $this->read(4))[1];
		$event_type = unpack('C', $this->read(1))[1];

		$this->read(4);
		//$server_id  = unpack('L', $this->read(4))[1];

		$event_size = unpack('L', $this->read(4))[1];

		//position of the next event
		$log_pos    = unpack('L', $this->read(4))[1];//

		$this->read(2);
		//$flags      = unpack('S', $this->read(2))[1];

		$event_size_without_header = $checkSum === true ? ($event_size -23) : $event_size - 19;



		switch ($event_type) {
			// 映射fileds相关信息
			case EventType::TABLE_MAP_EVENT: {
				//RowEvent::tableMap($this, $event_type);
				$this->tableMap();
			}
			break;
			case EventType::UPDATE_ROWS_EVENT_V2:
			case EventType::UPDATE_ROWS_EVENT_V1: {
				$data = $this->updateRow($event_type, $event_size_without_header);
					//RowEvent::updateRow($this, $event_type, $event_size_without_header);
				$data["event"]["time"] = date("Y-m-d H:i:s", $timestamp);
				}
				break;
			case EventType::WRITE_ROWS_EVENT_V1:
			case EventType::WRITE_ROWS_EVENT_V2: {
				$data = $this->addRow($event_type, $event_size_without_header);
					//RowEvent::addRow($this, $event_type, $event_size_without_header);
			$data["event"]["time"] = date("Y-m-d H:i:s", $timestamp);

		}
				break;
			case EventType::DELETE_ROWS_EVENT_V1:
			case EventType::DELETE_ROWS_EVENT_V2: {
				$data =  $this->delRow($event_type, $event_size_without_header);
					//RowEvent::delRow($this, $event_type, $event_size_without_header);
			$data["event"]["time"] = date("Y-m-d H:i:s", $timestamp);

		}
				break;
			case EventType::ROTATE_EVENT: {
				$log_pos = $this->readUint64();
				$file_name = $this->read($event_size_without_header - 8);
			}
				break;
			case EventType::HEARTBEAT_LOG_EVENT: {
				//心跳检测机制
				$binlog_name = $this->read($event_size_without_header);
				wing_debug('心跳事件 ' . $binlog_name);// . "\n";
			}
				break;
			default:
				echo "未知事件";
				break;
		}

		if (WING_DEBUG) {
			$msg  = $file_name;
			$msg .= '-- next pos -> '.$log_pos;
			$msg .= ' --  typeEvent -> '.$event_type;
			wing_log("slave_debug", $msg);
		}
		wing_log("slave_bin", $pack."\r\n\r\n");

		end:
		return [$data, $file_name, $log_pos];
	}

	public function read($length) {
		$length = (int)$length;
		$n='';

		if ($this->buffer) {
			$n = substr($this->buffer, 0 , $length);
			if(strlen($n) == $length) {
				$this->buffer = substr($this->buffer, $length);;
				return $n;
			} else {
				$this->buffer = '';
				$length = $length - strlen($n);
			}

		}

		for($i = $this->offset; $i < $this->offset + $length; $i++) {
			$n .= $this->packet[$i];
		}

		$this->offset += $length;

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
		if($c == Column::NULL) {
			return '';
		}
		if($c < Column::UNSIGNED_CHAR) {
			return $c;
		} elseif($c == Column::UNSIGNED_SHORT) {
			return $this->unpackUint16($this->read(Column::UNSIGNED_SHORT_LENGTH));

		}elseif($c == Column::UNSIGNED_INT24) {
			return $this->unpackInt24($this->read(Column::UNSIGNED_INT24_LENGTH));
		}
		elseif($c == Column::UNSIGNED_INT64) {
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

		return null;
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
	public function hasNext($size) {
		// 20解析server_id ...
		if ($this->offset + 1 - 20 < $size) {
			return false;
		}
		return true;
	}

	public function unread($data) {
		$this->buffer .= $data;
	}


	public function readTableId()
	{
		$a = (int)(ord($this->read(1)) & 0xFF);
		$a += (int)((ord($this->read(1)) & 0xFF) << 8);
		$a += (int)((ord($this->read(1)) & 0xFF) << 16);
		$a += (int)((ord($this->read(1)) & 0xFF) << 24);
		$a += (int)((ord($this->read(1)) & 0xFF) << 32);
		$a += (int)((ord($this->read(1)) & 0xFF) << 40);
		return $a;
	}

	public function tableMap()
	{
		$table_id = $this->readTableId();

		$this->read(2);
		//$flags = unpack('S', $this->read(2))[1];

		$schema_length = unpack("C", $this->read(1))[1];

		//database 数据库名称
		$this->schema_name = $this->read($schema_length);

		// 00
		$this->advance(1);

		$table_length = unpack("C", $this->read(1))[1];
		//数据表名称
		$this->table_name = $this->read($table_length);

		// 00
		$this->advance(1);

		$columns_num = $this->readCodedBinary();

		//
		$column_type_def = $this->read($columns_num);



		// 避免重复读取 表信息
//		if (isset(self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['table_id'])
//			&& self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['table_id']== self::$TABLE_ID) {
//			return $data;
//		}

		if (isset($this->table_map[$this->schema_name][$this->table_name]['table_id']) &&
			$this->table_map[$this->schema_name][$this->table_name]['table_id'] == $table_id
		) {
			return [
				'schema_name'=> $this->schema_name,
				'table_name' => $this->table_name,
				'table_id'   => $table_id
			];
		}

		$this->table_map[$this->schema_name][$this->table_name] = [
			'schema_name'=> $this->schema_name,
			'table_name' => $this->table_name,
			'table_id'   => $table_id
		];

//
//		self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME] = array(
//			'schema_name'=> $data['schema_name'],
//			'table_name' => $data['table_name'],
//			'table_id'   => self::$TABLE_ID
//		);


		$this->readCodedBinary();


		// fields 相应属性
		$colums = Db::getFields($this->schema_name, $this->table_name);

		$this->table_map[$this->schema_name][$this->table_name]['fields'] = [];

		for ($i = 0; $i < strlen($column_type_def); $i++) {
			$type = ord($column_type_def[$i]);
//			if(!isset($colums[$i])){
//				wing_log("slave_warn", var_export($colums, true).var_export($data, true));
//			}
			//self::$TABLE_MAP[self::$SCHEMA_NAME][self::$TABLE_NAME]['fields'][$i] =
			// BinLogColumns::parse($type, $colums[$i], $this);
			$this->table_map[$this->schema_name][$this->table_name]['fields'][$i] =
			 BinLogColumns::parse($type, $colums[$i], $this);
		}

		return [
			'schema_name'=> $this->schema_name,
			'table_name' => $this->table_name,
			'table_id'   => $table_id
		];
	}

	public function updateRow($event_type, $size)
	{

		//self::rowInit($pack, $event_type, $size);

		//$table_id =
			$this->readTableId();

		if (in_array($event_type, [EventType::DELETE_ROWS_EVENT_V2, EventType::WRITE_ROWS_EVENT_V2, EventType::UPDATE_ROWS_EVENT_V2])) {
			$this->read(2);
			//$flags = unpack('S', $this->read(2))[1];

			$extra_data_length = unpack('S', $this->read(2))[1];

			//$extra_data =
				$this->read($extra_data_length / 8);

		} else {
			$this->read(2);
			//$flags = unpack('S', $this->read(2))[1];
		}

		// Body
		$columns_num = $this->readCodedBinary();

		//$result = [];
		$len    = (int)(($columns_num + 7) / 8);


		$bitmap1 = $this->read($len);

		$bitmap2 = $this->read($len);


		//nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
//        $value['table'] = "";
//        $value['update'] = self::_getUpdateRows($result, $len);


		$value = [
			"database" => $this->schema_name,
			"table"    => $this->table_name,
			"event"    =>  [
				"event_type" => "update_rows",
				"data"       => self::_getUpdateRows($bitmap1, $bitmap2, $size)
			]
		];

		return $value;
	}

	private function _getUpdateRows($bitmap1, $bitmap2, $size) {
		$rows = [];
		while (!$this->hasNext($size)) {
			$rows[] = [
				"old_data" => $this->columnFormat($bitmap1),
				"new_data" => $this->columnFormat($bitmap2)
			];
		}
		return $rows;
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

	public static function BitGet($bitmap, $position)
	{
		$bit = $bitmap[intval($position / 8)];

		if (is_string($bit)) {

			$bit = ord($bit);
		}

		return $bit & (1 << ($position & 7));
	}

	public static function _is_null($null_bitmap, $position)
	{
		$bit = $null_bitmap[intval($position / 8)];
		if (is_string($bit)) {
			$bit = ord($bit);
		}


		return $bit & (1 << ($position % 8));
	}

	private function _read_string($size, $column)
	{
		$string = $this->read_length_coded_pascal_string($size);
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
		$value = $this->readUint8();
		if ( ($value & 0x80) != 0) {
			$res  = "";
			$mask = 0;
		}else {
			$mask = -1;
			$res  = "-";
		}
		$this->unread(pack('C', $value ^ 0x80));
		$size = $compressed_bytes[$comp_integral];
		if ($size > 0) {
			$value =  $this->read_int_be_by_size($size) ^ $mask;
			$res .= (string)$value;
		}


		for($i=0;$i<$uncomp_integral;$i++) {
			$value = unpack('N', $this->read(4))[1] ^ $mask;
			$res .= sprintf('%09d' , $value);
		}

		$res .= ".";
		for($i=0;$i<$uncomp_fractional;$i++) {
			$value = unpack('N', $this->read(4))[1] ^ $mask;
			$res .= sprintf('%09d' , $value);
		}

		$size = $compressed_bytes[$comp_fractional];
		if ($size > 0) {
			$value = $this->read_int_be_by_size($size) ^ $mask;

			$res.=sprintf('%0'.$comp_fractional.'d' , $value);
		}
		return number_format($res,$comp_fractional,'.','');
	}

	private function _read_datetime()
	{
		$value = $this->readUint64();
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
	private static function _read_binary_slice($binary, $start, $size, $data_length) {
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
		$data = $this->read_int_be_by_size(5);

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
		$microsecond = $this->_add_fsp_to_time($column);
		if($microsecond) {
			$time .='.'.$microsecond;
		}
		return $time;
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
			$microsecond = $this->read_int_be_by_size($read);
			if ($column['fsp'] % 2)
				$time = (int)($microsecond / 10);
			else
				$time = $microsecond;
		}
		return $time;
	}

	private function _read_date() {
		$time = $this->readUint24();

		if ($time == 0)  # nasty mysql 0000-00-00 dates
			return null;

		$year = ($time & ((1 << 15) - 1) << 9) >> 9;
		$month = ($time & ((1 << 4) - 1) << 5) >> 5;
		$day = ($time & ((1 << 5) - 1));
		if ($year == 0 || $month == 0 || $day == 0)
			return null;

		return $year.'-'.$month.'-'.$day;
	}


	private function columnFormat($cols_bitmap)
	{
		$values = [];

		//$l = (int)(($len * 8 + 7) / 8);
		$l = (int)((self::bitCount($cols_bitmap) + 7) / 8);

		# null bitmap length = (bits set in 'columns-present-bitmap'+7)/8
		# See http://dev.mysql.com/doc/internals/en/rows-event.html


		$null_bitmap = $this->read($l);

		$nullBitmapIndex = 0;
		foreach ($this->table_map[$this->schema_name][$this->table_name]['fields'] as $i => $value) {
			$column = $value;
			//var_dump($column);
			$name = $value['name'];
			$unsigned = $value['unsigned'];


			if (self::BitGet($cols_bitmap, $i) == 0) {
				$values[$name] = null;
				continue;
			}

			if (self::_is_null($null_bitmap, $nullBitmapIndex)) {
				$values[$name] = null;
			} elseif ($column['type'] == FieldType::TINY) {
				if ($unsigned)
					$values[$name] = unpack("C", $this->read(1))[1];
				else
					$values[$name] = unpack("c", $this->read(1))[1];
			} elseif ($column['type'] == FieldType::SHORT) {
				if ($unsigned)
					$values[$name] = unpack("S", $this->read(2))[1];
				else
					$values[$name] = unpack("s", $this->read(2))[1];
			} elseif ($column['type'] == FieldType::LONG) {

				if ($unsigned) {
					$values[$name] = unpack("I", $this->read(4))[1];
				} else {
					$values[$name] = unpack("i", $this->read(4))[1];
				}
			} elseif ($column['type'] == FieldType::INT24) {
				if ($unsigned)
					$values[$name] = $this->readUint24();
				else
					$values[$name] = $this->read_int24();
			} elseif ($column['type'] == FieldType::FLOAT)
				$values[$name] = unpack("f", $this->read(4))[1];
			elseif ($column['type'] == FieldType::DOUBLE)
				$values[$name] = unpack("d", $this->read(8))[1];
			elseif ($column['type'] == FieldType::VARCHAR ||
				$column['type'] == FieldType::STRING
			) {
				if ($column['max_length'] > 255)
					$values[$name] = $this->_read_string(2, $column);
				else
					$values[$name] = $this->_read_string(1, $column);
			} elseif ($column['type'] == FieldType::NEWDECIMAL) {

				//$precision = unpack('C', $this->read(1))[1];
				//$decimals  = unpack('C', $this->read(1))[1];

//var_dump($precision,$decimals);exit;
//precision = metadata[:precision]
//        scale = metadata[:decimals]
				$values[$name] = $this->read_new_decimal($column);
			} elseif ($column['type'] == FieldType::BLOB) {
				//ok
				$values[$name] = self::_read_string($column['length_size'], $column);

			}
			elseif ($column['type'] == FieldType::DATETIME) {

				$values[$name] = $this->_read_datetime();
			} elseif ($column['type'] == FieldType::DATETIME2) {
				//ok
				$values[$name] = $this->_read_datetime2($column);
			}elseif ($column['type'] == FieldType::TIME2) {

				$values[$name] = self::_read_time2($column);
			}
			elseif ($column['type'] == FieldType::TIMESTAMP2){
				//ok
				$time = date('Y-m-d H:i:m',$this->read_int_be_by_size(4));
				// 微妙
				$time .= '.' . self::_add_fsp_to_time($column);
				$values[$name] = $time;
			}
			elseif ($column['type'] == FieldType::DATE)
				$values[$name] = $this->_read_date();
			/*
		elseif ($column['type'] == FieldType::TIME:
			$values[$name] = self.__read_time()
		elseif ($column['type'] == FieldType::DATE:
			$values[$name] = self.__read_date()
			*/
			elseif ($column['type'] == FieldType::TIMESTAMP) {
				$values[$name] = date('Y-m-d H:i:s', $this->readUint32());
			}

			# For new date format:
			/*
						elseif ($column['type'] == FieldType::TIME2:
							$values[$name] = self.__read_time2(column)
						elseif ($column['type'] == FieldType::TIMESTAMP2:
							$values[$name] = self.__add_fsp_to_time(
									datetime.datetime.fromtimestamp(
										$this->read_int_be_by_size(4)), column)
						*/
			elseif ($column['type'] == FieldType::LONGLONG) {
				if ($unsigned) {
					$values[$name] = $this->readUint64();
				} else {
					$values[$name] = $this->readInt64();
				}

			} elseif($column['type'] == FieldType::ENUM) {
				$values[$name] = $column['enum_values'][$this->read_uint_by_size($column['size']) - 1];
			} else {
			}
			/*
			elseif ($column['type'] == FieldType::YEAR:
				$values[$name] = $this->read_uint8() + 1900
			elseif ($column['type'] == FieldType::SET:
				# We read set columns as a bitmap telling us which options
				# are enabled
				bit_mask = $this->read_uint_by_size(column.size)
				$values[$name] = set(
					val for idx, val in enumerate(column.set_values)
				if bit_mask & 2 ** idx
				) or None

			elseif ($column['type'] == FieldType::BIT:
				$values[$name] = self.__read_bit(column)
			elseif ($column['type'] == FieldType::GEOMETRY:
				$values[$name] = $this->read_length_coded_pascal_string(
						column.length_size)
			else:
				raise NotImplementedError("Unknown MySQL column type: %d" %
					(column.type))
			*/
			$nullBitmapIndex += 1;
		}
		//$values['table_name'] = self::$TABLE_NAME;
		return $values;
	}


	public function addRow( $event_type, $size)
	{
		//$table_id =
		$this->readTableId();

		if (in_array($event_type, [EventType::DELETE_ROWS_EVENT_V2, EventType::WRITE_ROWS_EVENT_V2, EventType::UPDATE_ROWS_EVENT_V2])) {
			$this->read(2);
			//$flags = unpack('S', $this->read(2))[1];

			$extra_data_length = unpack('S', $this->read(2))[1];

			//$extra_data =
			$this->read($extra_data_length / 8);

		} else {
			$this->read(2);
			//$flags = unpack('S', $this->read(2))[1];
		}

		// Body
		$columns_num = $this->readCodedBinary();

		//$result = [];
		// ？？？？
		//$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", $this->read(1))[1];
		//$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
		$len = (int)(($columns_num + 7) / 8);


		$bitmap = $this->read($len);

		//nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8

		$value = [
			"database" => $this->schema_name,
			"table"    => $this->table_name,
			"event"    =>  [
				"event_type" => "write_rows",
				"data"       => self::_getAddRows($bitmap, $size)
			]
		];
		return $value;
	}

	public function delRow($event_type, $size)
	{
		//$table_id =
		$this->readTableId();

		if (in_array($event_type, [EventType::DELETE_ROWS_EVENT_V2, EventType::WRITE_ROWS_EVENT_V2, EventType::UPDATE_ROWS_EVENT_V2])) {
			$this->read(2);
			//$flags = unpack('S', $this->read(2))[1];

			$extra_data_length = unpack('S', $this->read(2))[1];

			//$extra_data =
			$this->read($extra_data_length / 8);

		} else {
			$this->read(2);
			//$flags = unpack('S', $this->read(2))[1];
		}

		// Body
		$columns_num = $this->readCodedBinary();

		//$result = [];
		// ？？？？
		//$result['extra_data'] = getData($data, );
//        $result['columns_length'] = unpack("C", $this->read(1))[1];
		//$result['schema_name']   = getData($data, 29, 28+$result['schema_length'][1]);
		$len = (int)(($columns_num + 7) / 8);


		$bitmap = $this->read($len);


		//nul-bitmap, length (bits set in 'columns-present-bitmap1'+7)/8
		//$value['del'] = self::_getDelRows($result, $len);

		$value = [
			"database" => $this->schema_name,
			"table"    => $this->table_name,
			"event"    =>  [
				"event_type" => "delete_rows",
				"data"       => self::_getDelRows($bitmap, $size)
			]
		];
		return $value;
	}

	private function _getDelRows($bitmap, $size) {
		$rows = [];
		while(!$this->hasNext($size)) {
			$rows[] = $this->columnFormat($bitmap);
		}
		return $rows;
	}

	private function  _getAddRows($bitmap, $size) {
		$rows = [];

		while(!$this->hasNext($size)) {
			$rows[] = $this->columnFormat($bitmap);
		}
		return $rows;
	}




}