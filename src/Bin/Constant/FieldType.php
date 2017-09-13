<?php namespace Wing\Bin\Constant;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 23:19
 * 字段类型
 */
class FieldType
{
	const   FIELD_TYPE_DECIMAL = 0x00;
    const 	FIELD_TYPE_TINY = 0x01;
    const 	FIELD_TYPE_SHORT = 0x02;
    const 	FIELD_TYPE_LONG = 0x03;
    const 	FIELD_TYPE_FLOAT = 0x04;
    const 	FIELD_TYPE_DOUBLE = 0x05;
    const 	FIELD_TYPE_NULL =0x06;
	const 	FIELD_TYPE_TIMESTAMP = 0x07;
	const 	FIELD_TYPE_LONGLONG = 0x08;
	const 	FIELD_TYPE_INT24 = 0x09;
	const 	FIELD_TYPE_DATE = 0x0A;
	const 	FIELD_TYPE_TIME = 0x0B;
	const 	FIELD_TYPE_DATETIME = 0x0C;
	const 	FIELD_TYPE_YEAR = 0x0D;
	const 	FIELD_TYPE_NEWDATE = 0x0E;
	const 	FIELD_TYPE_VARCHAR = 0x0F;// (new in MySQL 5.0)
	const 	FIELD_TYPE_BIT = 0x10;// (new in MySQL 5.0)
	const 	FIELD_TYPE_NEWDECIMAL = 0xF6;// (new in MYSQL 5.0)
	const 	FIELD_TYPE_ENUM = 0xF7;
	const 	FIELD_TYPE_SET = 0xF8;
	const 	FIELD_TYPE_TINY_BLOB = 0xF9;
	const 	FIELD_TYPE_MEDIUM_BLOB = 0xFA;
	const 	FIELD_TYPE_LONG_BLOB = 0xFB;
	const 	FIELD_TYPE_BLOB = 0xFC;
	const 	FIELD_TYPE_VAR_STRING = 0xFD;
	const 	FIELD_TYPE_STRING = 0xFE;
	const 	FIELD_TYPE_GEOMETRY = 0xFF;
}