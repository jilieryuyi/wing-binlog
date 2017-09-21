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
//	const IN_TRANS 				=	0x0001;
//	const AUTOCOMMIT 			=	0x0002;
//	const CURSOR_EXISTS			=	0x0040;
//	const LAST_ROW_SENT			=	0x0080;
//	const DB_DROPPED			=	0x0100;
//	const NO_BACKSLASH_ESCAPES	=	0x0200;
//	const METADATA_CHANGED		=	0x0400;
const IN_TRANS =    1;
const AUTOCOMMIT =   2;	/* Server in auto_commit mode */
const MORE_RESULTS_EXISTS = 8;    /* Multi query - next query exists */
const QUERY_NO_GOOD_INDEX_USED = 16;
const QUERY_NO_INDEX_USED =     32;
	/**
	The server was able to fulfill the clients request and opened a
	read-only non-scrollable cursor for a query. This flag comes
	in reply to COM_STMT_EXECUTE and COM_STMT_FETCH commands.
	 */
const CURSOR_EXISTS = 64;
	/**
	This flag is sent when a read-only cursor is exhausted, in reply to
	COM_STMT_FETCH command.
	 */
const LAST_ROW_SENT = 128;
const DB_DROPPED =       256; /* A database was dropped */
const NO_BACKSLASH_ESCAPES = 512;
	/**
	Sent to the client if after a prepared statement reprepare
	we discovered that the new statement returns a different 
	number of result set columns.
	 */
const METADATA_CHANGED = 1024;
const QUERY_WAS_SLOW   =       2048;

	/**
	To mark ResultSet containing output parameter values.
	 */
const PS_OUT_PARAMS     =       4096;

	/**
	Set at the same time as SERVER_STATUS_IN_TRANS if the started
	multi-statement transaction is a read-only transaction. Cleared
	when the transaction commits or aborts. Since this flag is sent
	to clients in OK and EOF packets, the flag indicates the
	transaction status at the end of command execution.
	 */
const IN_TRANS_READONLY = 8192;

	/**
	This status flag, when on, implies that one of the state information has
	changed on the server because of the execution of the last statement.
	 */
const SESSION_STATE_CHANGED =  (1 << 14);

}