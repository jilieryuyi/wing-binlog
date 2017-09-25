<?php namespace Wing\Bin\Constant;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:19
 * 字段类型
 * include/mysql_com.h
 * include/mysql.h.pp
 */
class FieldType
{
	/*const   DECIMAL 	= 0x00;
    const 	TINY 		= 0x01;
    const 	SHORT 		= 0x02;
    const 	LONG 		= 0x03;
    const 	FLOAT		= 0x04;
    const 	DOUBLE 		= 0x05;
    const 	NULL 		= 0x06;
	const 	TIMESTAMP	= 0x07;
    const 	BIGINT	    = 0x08;
    const 	LONGLONG	= 0x08;
	const 	INT24 		= 0x09;
	const 	DATE 		= 0x0A;
	const 	TIME 		= 0x0B;
	const 	DATETIME 	= 0x0C;
	const 	YEAR 		= 0x0D;
	const 	NEWDATE 	= 0x0E;
	const 	VARCHAR 	= 0x0F;// (new in MySQL 5.0)
	const 	BIT 		= 0x10;// (new in MySQL 5.0)
    const   TIMESTAMP2 	= 0x11;//17;
    const   DATETIME2 	= 0x12;//18;
    const   TIME2 		= 0x13;//19;
	const 	NEWDECIMAL 	= 0xF6;// (new in MYSQL 5.0)
	const 	ENUM 		= 0xF7;
	const 	SET 		= 0xF8;
	const 	TINY_BLOB 	= 0xF9;
	const 	MEDIUM_BLOB = 0xFA;
	const 	LONG_BLOB 	= 0xFB;
	const 	BLOB 		= 0xFC; //252
	const 	VAR_STRING 	= 0xFD; //253
	const 	STRING 		= 0xFE;
	const 	GEOMETRY 	= 0xFF;
    const   CHAR 		= self::TINY;
    const   INTERVAL 	= self::ENUM;
    const   JSON        = 245;*/
//libmysql/libmysql.c 2933
const DECIMAL       = 0;

const TINY          = 1; //1 byte store_param_tinyint
const SHORT         = 2; //2 bytes store_param_short
const LONG          = 3; //4 bytes store_param_int32
const FLOAT         = 4; //4 bytes store_param_float
const DOUBLE        = 5; // 8bytes store_param_double
const NULL          = 6; //int_is_null_true == 1
const TIMESTAMP     = 7; //store_param_datetime

const LONGLONG      = 8; //8 bytes store_param_int64
const BIGINT        = 8; //alias LONGLONG 8 bytes store_param_int64

const INT24         = 9;
const DATE          = 10;
const TIME          = 11;
const DATETIME      = 12;
const YEAR          = 13;
const NEWDATE       = 14;
const VARCHAR       = 15;//store_param_str
const BIT           = 16;
const TIMESTAMP2    = 17;
const DATETIME2     = 18;
const TIME2         = 19;
const JSON          = 245;//store_param_str
const NEWDECIMAL    = 246;//store_param_str (new in MYSQL 5.0)
const ENUM          = 247;
const SET           = 248;
const TINY_BLOB     = 249;//store_param_str
const MEDIUM_BLOB   = 250;//store_param_str
const LONG_BLOB     = 251;//store_param_str
const BLOB          = 252;//store_param_str
const VAR_STRING    = 253;//store_param_str
const STRING        = 254;//store_param_str
const GEOMETRY      = 255;


	private $value;
	public $type;

	public function __construct($value, $type = -1)
	{
		$this->value = $value;
		if ($type === -1) {
			$this->type = $this->getType();
		} else {
			$this->type = $type;
		}
	}

	private function isInt()
	{
		return (intval($this->value) - $this->value) == 0;
	}

	/**
	 * @return int
	 */
	public function getType()
	{
		if(!is_numeric($this->value)) {
			return self::VAR_STRING;
		}

		if ($this->isInt()) {
			$this->value = intval($this->value);
			//tinyint
			if((-1 << 7) <= $this->value && $this->value <= ((1 << 7)-1)) {
				return self::TINY;
			}
			//2字节
			else if((-1 << 15) <= $this->value && $this->value <= ((1 << 15)-1)) {
				self::SHORT;
			}
			//3个字节
			else if((-1 << 23) <= $this->value && $this->value <= ((1 << 23)-1)) {
				self::INT24;
			}
			//4个字节
			else if((-1 << 31) <= $this->value && $this->value <= ((1 << 31)-1)) {
				return self::BIGINT;
			}
			return self::BIGINT;
		} else {
			//浮点数
			//float型数据的取值范围在-3.4*10^38到+3.4*10^38次之间
//            if ($this->value >= -3.4*pow(10, 38) && $this->value < 3.4 * pow(10, 38)) {
//                return self::FLOAT;
//            }
		}

		//其他的一律以字符串处理，有待验证
		return self::VAR_STRING;
	}

	/**
	 * string
	 */
	private function storeLength()
	{
		$length = strlen($this->value);
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
		return chr(254).self::packIn64($length);//pack("P", $length);
	}

	private function packIn64($value)
	{
		$def_temp  = $value;
		$def_temp2 = ($value >> 32);
		$data = chr($def_temp).chr($def_temp>> 8).chr($def_temp >> 16).chr($def_temp >> 24);
		$data .= chr($def_temp2).chr($def_temp2>> 8).chr($def_temp2 >> 16).chr($def_temp2 >> 24);
		return $data;
	}

	public function pack()
	{
		switch ($this->type) {
			case self::TINY:
				return chr($this->value);
				break;
			case self::SHORT:
				return chr($this->value).chr($this->value >> 8);
				break;
			case self::INT24:
				return chr($this->value).chr($this->value >> 8).chr($this->value >> 16);
				break;
			case self::BIGINT:
				$def_temp  = $this->value;
				$def_temp2 = ($this->value >> 32);
				$data      = chr($def_temp).chr($def_temp>> 8).chr($def_temp >> 16).chr($def_temp >> 24);
				$data     .= chr($def_temp2).chr($def_temp2>> 8).chr($def_temp2 >> 16).chr($def_temp2 >> 24);
				return $data;
				break;
			case self::VAR_STRING:
				return $this->storeLength().$this->value;

		}

		//include/big_endian.h
		//self::FLOAT;
		//float4store

		//self::DOUBLE;
		//float8store

		return null;
	}


	public static function parse(array $params)
	{
		$res = [];
		foreach ($params as $value) {
			$res[] = new self($value);
		}
		return $res;
	}

	public static function fieldtype2str($type)
	{
		switch ($type) {
			case self::BIT:         return "BIT";
			case self::BLOB:        return "BLOB";
			case self::DATE:        return "DATE";
			case self::DATETIME:    return "DATETIME";
			case self::NEWDECIMAL:  return "NEWDECIMAL";
			case self::DECIMAL:     return "DECIMAL";
			case self::DOUBLE:      return "DOUBLE";
			case self::ENUM:        return "ENUM";
			case self::FLOAT:       return "FLOAT";
			case self::GEOMETRY:    return "GEOMETRY";
			case self::INT24:       return "INT24";
			case self::JSON:        return "JSON";
			case self::LONG:        return "LONG";
			case self::LONGLONG:    return "LONGLONG";
			case self::LONG_BLOB:   return "LONG_BLOB";
			case self::MEDIUM_BLOB: return "MEDIUM_BLOB";
			case self::NEWDATE:     return "NEWDATE";
			case self::NULL:        return "NULL";
			case self::SET:         return "SET";
			case self::SHORT:       return "SHORT";
			case self::STRING:      return "STRING";
			case self::TIME:        return "TIME";
			case self::TIMESTAMP:   return "TIMESTAMP";
			case self::TINY:        return "TINY";
			case self::TINY_BLOB:   return "TINY_BLOB";
			case self::VAR_STRING:  return "VAR_STRING";
			case self::YEAR:        return "YEAR";
			default:                     return "?-unknown-?";
		}
	}
}