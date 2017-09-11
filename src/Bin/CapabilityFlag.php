<?php namespace Wing\Bin;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/8
 * Time: 22:59
 */
class CapabilityFlag 
{
	const CLIENT_LONG_PASSWORD 		= 1;
	//Use the improved version of Old Password Authentication. More...
 
	const CLIENT_FOUND_ROWS 		= 2;
 	//Send found rows instead of affected rows in EOF_Packet. More...
 
	const CLIENT_LONG_FLAG 			= 4;
 	//Get all column flags. More...
 
	const CLIENT_CONNECT_WITH_DB 	= 8;
 	//Database (schema) name can be specified on connect in Handshake Response Packet. More...
 
	const CLIENT_NO_SCHEMA  		= 16;
 	//Don't allow database.table.column. More...
 
	const CLIENT_COMPRESS  			= 32;
 	//	Compression protocol supported. More...
 
	const CLIENT_ODBC 				= 64;
	//Special handling of ODBC behavior. More...
 
	const CLIENT_LOCAL_FILES 		= 128;
 	//Can use LOAD DATA LOCAL. More...
 
	const CLIENT_IGNORE_SPACE 		= 256;
 	//Ignore spaces before '('. More...
 
	const CLIENT_PROTOCOL_41 		= 512;
 	//New 4.1 protocol. More...
 
	const CLIENT_INTERACTIVE  		= 1024;
 	//This is an interactive client. More...
 
	const CLIENT_SSL 				= 2048;
 	//Use SSL encryption for the session. More...
 
	const CLIENT_IGNORE_SIGPIPE 	= 4096;
 	//Client only flag. More...
 
	const CLIENT_TRANSACTIONS 		= 8192;
 	//	Client knows about transactions. More...
 
	const CLIENT_RESERVED 			= 16384;
 	//	DEPRECATED: Old flag for 4.1 protocol. More...
 
	const CLIENT_RESERVED2  		= 32768;
 	//DEPRECATED: Old flag for 4.1 authentication. More...

	const CLIENT_SECURE_CONNECTION	= (1 << 15);
	const CLIENT_MULTI_STATEMENTS 	= (1 << 16);
 	//Enable/disable multi-stmt support. More...
 
	const CLIENT_MULTI_RESULTS  	= (1 << 17);
 	//Enable/disable multi-results. More...
 
	const CLIENT_PS_MULTI_RESULTS 	= (1 << 18);
 	//Multi-results and OUT parameters in PS-protocol. More...
 
	const CLIENT_PLUGIN_AUTH  		= (1 << 19);
 	//Client supports plugin authentication. More...
 
	const CLIENT_CONNECT_ATTRS		 = (1 << 20);
 	//Client supports connection attributes. More...
 
	const CLIENT_PLUGIN_AUTH_LENENC_CLIENT_DATA = (1 << 21);
 	//Enable authentication response packet to be larger than 255 bytes. More...
 
	const CLIENT_CAN_HANDLE_EXPIRED_PASSWORDS 	= (1 << 22);
 	//Don't close the connection for a user account with expired password. More...
 
	const CLIENT_SESSION_TRACK 					= (1 << 23);
 	//Capable of handling server state change information. More...
 
	const CLIENT_DEPRECATE_EOF					= (1 << 24);
 	//Client no longer needs EOF_Packet and will use OK_Packet instead. More...
 
	const CLIENT_SSL_VERIFY_SERVER_CERT 		= (1 << 30);
 	//Verify server certificate. More...
 
	const CLIENT_REMEMBER_OPTIONS				= (1 << 31);
    

//    public static function init() {
//        self::$LONG_PASSWORD = 1;
//        self::$FOUND_ROWS = 1 << 1;
//        self::$LONG_FLAG = 1 << 2;
//        self::$LONG_FLAG = 1 << 2;
//        self::$CONNECT_WITH_DB = 1 << 3;
//        self::$NO_SCHEMA = 1 << 4;
//        self::$COMPRESS = 1 << 5;
//        self::$ODBC = 1 << 6;
//        self::$LOCAL_FILES = 1 << 7;
//        self::$IGNORE_SPACE = 1 << 8;
//        self::$PROTOCOL_41 = 1 << 9;
//        self::$INTERACTIVE = 1 << 10;
//        self::$SSL = 1 << 11;
//        self::$IGNORE_SIGPIPE = 1 << 12;
//        self::$TRANSACTIONS = 1 << 13;
//        self::$SECURE_CONNECTION = 1 << 15;
//        self::$MULTI_STATEMENTS = 1 << 16;
//        self::$MULTI_RESULTS = 1 << 17;
		const CAPABILITIES = (self::CLIENT_LONG_PASSWORD | self::CLIENT_LONG_FLAG | self::CLIENT_TRANSACTIONS |
            self::CLIENT_PROTOCOL_41 | self::CLIENT_SECURE_CONNECTION);
//    }
}

//\Wing\Bin\CapabilityFlag::init();