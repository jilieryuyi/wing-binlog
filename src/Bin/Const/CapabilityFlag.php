<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 22:59
 * 服务器权能标志：用于与客户端协商通讯方式，
 * 各标志位含义如下（参考MySQL源代码/include/mysql_com.h中的宏定义）
 *
 *  标志位名称					标志位	说明
	CLIENT_LONG_PASSWORD		0x0001	new more secure passwords
	CLIENT_FOUND_ROWS			0x0002	Found instead of affected rows
	CLIENT_LONG_FLAG			0x0004	Get all column flags
	CLIENT_CONNECT_WITH_DB		0x0008	One can specify db on connect
	CLIENT_NO_SCHEMA			0x0010	Do not allow database.table.column
	CLIENT_COMPRESS				0x0020	Can use compression protocol
	CLIENT_ODBC					0x0040	Odbc client
	CLIENT_LOCAL_FILES			0x0080	Can use LOAD DATA LOCAL
	CLIENT_IGNORE_SPACE			0x0100	Ignore spaces before '('
	CLIENT_PROTOCOL_41			0x0200	New 4.1 protocol
	CLIENT_INTERACTIVE			0x0400	This is an interactive client
	CLIENT_SSL					0x0800	Switch to SSL after handshake
	CLIENT_IGNORE_SIGPIPE		0x1000	IGNORE sigpipes
	CLIENT_TRANSACTIONS			0x2000	Client knows about transactions
	CLIENT_RESERVED				0x4000	Old flag for 4.1 protocol
	CLIENT_SECURE_CONNECTION	0x8000	New 4.1 authentication
	CLIENT_MULTI_STATEMENTS		0x0001 0000	Enable/disable multi-stmt support
	CLIENT_MULTI_RESULTS		0x0002 0000	Enable/disable multi-results
 */
class CapabilityFlag 
{
	const CLIENT_LONG_PASSWORD 		= 1;
	const CLIENT_FOUND_ROWS 		= 2;
	const CLIENT_LONG_FLAG 			= 4;
	const CLIENT_CONNECT_WITH_DB 	= 8;
	const CLIENT_NO_SCHEMA  		= 16;
	const CLIENT_COMPRESS  			= 32;
	const CLIENT_ODBC 				= 64;
	const CLIENT_LOCAL_FILES 		= 128;
	const CLIENT_IGNORE_SPACE 		= 256;
	const CLIENT_PROTOCOL_41 		= 512;
	const CLIENT_INTERACTIVE  		= 1024;
	const CLIENT_SSL 				= 2048;
	const CLIENT_IGNORE_SIGPIPE 	= 4096;
	const CLIENT_TRANSACTIONS 		= 8192;
	const CLIENT_RESERVED 			= 16384;
	const CLIENT_RESERVED2  		= 32768;
	const CLIENT_SECURE_CONNECTION	= (1 << 15);
	const CLIENT_MULTI_STATEMENTS 	= (1 << 16);
	const CLIENT_MULTI_RESULTS  	= (1 << 17);
	const CLIENT_PS_MULTI_RESULTS 	= (1 << 18);
	const CLIENT_PLUGIN_AUTH  		= (1 << 19);
	const CLIENT_CONNECT_ATTRS		 = (1 << 20);
	const CLIENT_PLUGIN_AUTH_LENENC_CLIENT_DATA = (1 << 21);
	const CLIENT_CAN_HANDLE_EXPIRED_PASSWORDS 	= (1 << 22);
	const CLIENT_SESSION_TRACK 					= (1 << 23);
	const CLIENT_DEPRECATE_EOF					= (1 << 24);
	const CLIENT_SSL_VERIFY_SERVER_CERT 		= (1 << 30);
	const CLIENT_REMEMBER_OPTIONS				= (1 << 31);

	//默认的服务器权能标记 需要就添加上就可以了
	const DEFAULT_CAPABILITIES =
		(
			self::CLIENT_LONG_PASSWORD |
			self::CLIENT_LONG_FLAG |
			self::CLIENT_TRANSACTIONS |
            self::CLIENT_PROTOCOL_41 |
			self::CLIENT_SECURE_CONNECTION
		);
}