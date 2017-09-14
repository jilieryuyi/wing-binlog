<?php namespace Wing\Bin\Constant;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/13
 * Time: 23:17
 * mysql表字段标志位
 * mysql-5.7.19\include\mysql_com.h
 */
class FieldFlag
{
	const 	NOT_NULL_FLAG = 1;
	const 	PRI_KEY_FLAG = 2;
	const 	UNIQUE_KEY_FLAG = 4;
	const 	MULTIPLE_KEY_FLAG = 8;
	const 	BLOB_FLAG = 16;
	const 	UNSIGNED_FLAG = 32;
	const 	ZEROFILL_FLAG = 64;
	const 	BINARY_FLAG = 128;
	const 	ENUM_FLAG = 256;
	const 	AUTO_INCREMENT_FLAG = 512;
	const 	TIMESTAMP_FLAG = 1024;
	const 	SET_FLAG = 2048;

	const 	NO_DEFAULT_VALUE_FLAG= 4096;	/* Field doesn't have default value */
	const 	ON_UPDATE_NOW_FLAG= 8192 ;        /* Field is set to NOW on UPDATE */
	const 	NUM_FLAG=	32768	;	/* Field is num (for clients) */
	const 	PART_KEY_FLAG	=16384	;	/* Intern; Part of some key */
	const 	GROUP_FLAG	=32768	;	/* Intern: Group field */
	const 	UNIQUE_FLAG	=65536	;	/* Intern: Used by sql_yacc */
	const 	BINCMP_FLAG	=131072	;	/* Intern: Used by sql_yacc */
	const 	GET_FIXED_FIELDS_FLAG =(1 << 18); /* Used to get fields in item tree */
	const 	FIELD_IN_PART_FUNC_FLAG =(1 << 19);/* Field part of partition func */
	/**
	Intern: Field in TABLE object for new version of altered table,
	which participates in a newly added index.
	 */
	const	FIELD_IN_ADD_INDEX =(1 << 20);
	const 	FIELD_IS_RENAMED =(1<< 21)  ;     /* Intern: Field is being renamed */
	const 	FIELD_FLAGS_STORAGE_MEDIA =22  ;  /* Field storage media, bit 22-23 */
	const 	FIELD_FLAGS_STORAGE_MEDIA_MASK= (3 << 22);
	const 	FIELD_FLAGS_COLUMN_FORMAT =24  ;  /* Field column format, bit 24-25 */
	const 	FIELD_FLAGS_COLUMN_FORMAT_MASK= (3 << 24);
	const 	FIELD_IS_DROPPED= (1<< 26)    ;   /* Intern: Field is being dropped */
	const 	EXPLICIT_NULL_FLAG =(1<< 27)  ;   /* Field is explicitly specified as NULL by the user */
}