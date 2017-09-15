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
	const   DECIMAL 	= 0x00;
    const 	TINY 		= 0x01;
    const 	SHORT 		= 0x02;
    const 	LONG 		= 0x03;
    const 	FLOAT		= 0x04;
    const 	DOUBLE 		= 0x05;
    const 	NULL 		= 0x06;
	const 	TIMESTAMP	= 0x07;
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
	const 	BLOB 		= 0xFC;
	const 	VAR_STRING 	= 0xFD; //253
	const 	STRING 		= 0xFE;
	const 	GEOMETRY 	= 0xFF;
    const   CHAR 		= self::TINY;
    const   INTERVAL 	= self::ENUM;
}