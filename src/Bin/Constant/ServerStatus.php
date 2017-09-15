<?php namespace Wing\Bin\Constant;
/**
 * MysqServerStatus.php
 * User: huangxiaoan
 * Created: 2017/9/13 16:34
 * Email: huangxiaoan@xunlei.com
 * 服务器状态：状态值定义如下（参考MySQL源代码/include/mysql_com.h中的宏定义）
 */
class ServerStatus
{
	const IN_TRANS 				=	0x0001;
	const AUTOCOMMIT 			=	0x0002;
	const CURSOR_EXISTS			=	0x0040;
	const LAST_ROW_SENT			=	0x0080;
	const DB_DROPPED			=	0x0100;
	const NO_BACKSLASH_ESCAPES	=	0x0200;
	const METADATA_CHANGED		=	0x0400;
}