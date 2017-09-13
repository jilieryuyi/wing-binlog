<?php namespace Wing\Bin\Constant;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/13
 * Time: 23:17
 * mysql表字段标志位
 */
class FieldFlag
{
	const 	NOT_NULL_FLAG = 0x0001;
	const 	PRI_KEY_FLAG = 0x0002;
	const 	UNIQUE_KEY_FLAG = 0x0004;
	const 	MULTIPLE_KEY_FLAG = 0x0008;
	const 	BLOB_FLAG = 0x0010;
	const 	UNSIGNED_FLAG = 0x0020;
	const 	ZEROFILL_FLAG = 0x0040;
	const 	BINARY_FLAG = 0x0080;
	const 	ENUM_FLAG = 0x0100;
	const 	AUTO_INCREMENT_FLAG = 0x0200;
	const 	TIMESTAMP_FLAG = 0x0400;
	const 	SET_FLAG = 0x0800;
}