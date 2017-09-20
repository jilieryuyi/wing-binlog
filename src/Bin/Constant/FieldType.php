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
}