<?php namespace Wing\Library\Mysql;
use Wing\Bin\Auth\Auth;
use Wing\Bin\Auth\ServerInfo;
use Wing\Bin\Constant\CharacterSet;
use Wing\Bin\Constant\Trans;
use Wing\Bin\Mysql;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/20
 * Time: 15:31
 */
class PDO
{
	/*
	 * @var int $protocol_version
	 * Returns the version of the MySQL protocol used
	 * 服务协议版本号：该值由 PROTOCOL_VERSION
	 * 宏定义决定（参考MySQL源代码/include/mysql_version.h头文件定义）
	 * mysql-server/config.h.cmake 399
	 */
	public $protocol_version;
	/**
	 * @var string $server_info like 5.7.17-log
	 * Returns the version of the MySQL server
	 * mysql-server/include/mysql_version.h.in 12行
	 * mysql-server/cmake/mysql_version.cmake 59行
	 */
	public $server_info;
	/**
	 * @var int $server_version like 50717
	 * Returns the version of the MySQL server as an integer
	 * == main_version*10000 + minor_version *100 + sub_version
     */
	public $server_version;

	/**
	 * @var int $character_set
	 * define int Binlog/src/Bin/Constant/CharacterSet.php
	 */
	private $character_set = '';

	/**
	 * @var string $salt
	 * use for encode password
	 */
	public $salt = '';

	/**
	 * @var int $thread_id
	 * Returns the thread ID for the current connection\
	 */
	public $thread_id;
	public $auth_plugin_name = '';
	public $capability_flag;

	public $affected_rows;
    public $client_info;//Get MySQL client info
    public $client_version = "1.0";//(){}//Returns the MySQL client version as a number
    public $connect_errno;//(){}//Returns the error code from last connect call
    public $connect_error;//(){}//Returns a string description of the last connect error
    public $errno;//(){}//Returns the error code for the most recent function call
    public $error_list;//(){}//Returns a list of errors from the last command executed
    public $error;//(){}//Returns a string description of the last error
    public $field_count;//(){}//Returns the number of columns for the most recent query
    public $host_info;//(){}//Returns a string representing the type of connection used
	public $info;//(){}//Retrieves information about the most recently executed query
    public $insert_id;//(){}//Returns the auto generated id used in the latest query
    public $sqlstate;//(){}//Returns the SQLSTATE error from previous MySQL operation
    public $warning_count;//(){}//Returns the number of warnings from the last query for the given link

	private $socket;

    //Open a new connection to the MySQL server
    public function __construct($host, $username, $passwd, $dbname, $port = 3306)
    {
		/**
		 * @var ServerInfo $server_info
		 */
        //认证
        list($this->socket, $server_info) = \Wing\Bin\Auth\Auth::execute($host,$username,$passwd, $dbname, $port);
        $this->autocommit(true);

		$this->protocol_version = $server_info->protocol_version;
		$this->server_info 		= $server_info->server_info;
		$this->thread_id 		= $server_info->thread_id;
		$this->character_set 	= $server_info->character_set;
		$this->salt 			= $server_info->salt;
		$this->auth_plugin_name = $server_info->auth_plugin_name;
		$this->capability_flag 	= $server_info->capability_flag;

		var_dump($server_info);

		//main_version*10000 + minor_version *100 + sub_version
		list($main_version, $minor_version, $sub_version) = explode(".", $this->server_info);
		$sub_version = preg_replace("/\D/","", $sub_version);
		$this->server_version = $main_version*10000 + $minor_version *100 + $sub_version;
    }

    public function __destruct()
	{
		$res = $this->close();
		Auth::free();
		return $res;
	}

	/*
	 * 	Closes a previously opened database connection
	 */
	public function close()
	{
		return Mysql::close();
	}

	/**
	 * Returns the default character set for the database connection
	 *
	 * @return string like "utf8_general_ci"
	 */
	public function character_set_name()
	{
		return CharacterSet::getCharacterSet($this->character_set);
	}

	/**
	 * set autocommit
	 *
	 * @throws \Exception
	 * @param bool $auto
	 * @return bool
	 */
    public function autocommit($auto = true)
    {
        $auto = $auto?1:0;
        return Mysql::query('set autocommit='.$auto);
    }

    /**
	 * Starts a transaction
	 *
	 * @param int $mode
	 * @param string $name Savepoint name for the transaction.
	 */
    public function begin_transaction($mode = Trans::NO_OPT, $name = '')
    {
		$sql = '';
        if ($mode & Trans::WITH_CONSISTENT_SNAPSHOT) {
        	if ($sql) $sql .= ',';
           $sql .=" WITH CONSISTENT SNAPSHOT";
        }

        //5.6.5之前的版本不支持
		if ( $this->server_version >= 50605) {
			if ($mode & (Trans::READ_WRITE | Trans::READ_ONLY)) {
				if ($mode & Trans::READ_WRITE) {
					if ($sql) $sql .= ',';
					$sql .= " READ WRITE";
				} else if ($mode & Trans::READ_ONLY) {
					if ($sql) $sql .= ',';
					$sql .= " READ ONLY";
				}
			}
		}

		$parse_sql = 'START TRANSACTION';

		if ($name) {
			//mysql-server/mysys/charset.c  777 需要过滤
			$parse_sql .= ' '.$this->real_escape_string($name);
		}

		$parse_sql .= $sql;

        echo $parse_sql;
        $this->autocommit(false);
        return Mysql::query($parse_sql);
    }

    //Changes the user of the specified database connection
    public function change_user()
    {

    }

	//Commits the current transaction
    public function commit()
	{

	}
    public function debug(){}//Performs debugging operations
    public function dump_debug_info(){}//Dump debugging information into the log
    public function get_charset(){}//Returns a character set object
    //public function get_client_info(){}//Get MySQL client info
    //public function get_client_stats(){}//Returns client per-process statistics
    public function get_client_version(){}//Returns the MySQL client version as an integer
    public function get_connection_stats(){}//Returns statistics about the client connection
    public function get_warnings(){}//Get result of SHOW WARNINGS
    public function init(){}//Initializes MySQLi and returns a resource for use with mysqli_real_connect()
    public function kill(){}//Asks the server to kill a MySQL thread
    public function more_results(){}//Check if there are any more query results from a multi query
    public function multi_query(){}//Performs a query on the database
    public function next_result(){}//Prepare next result from multi_query
    public function options(){}//Set options
    public function ping(){}//Pings a server connection, or tries to reconnect if the connection has gone down
    public function poll(){}//Poll connections
    public function prepare(){}//Prepare an SQL statement for execution
    public function query(){}//Performs a query on the database
    public function real_connect(){}//Opens a connection to a mysql server
    public function real_escape_string($str){
		//mysql-server/mysys/charset.c  777 需要过滤
		return $str;
	}//Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
    public function real_query(){}//Execute an SQL query
    public function reap_async_query(){}//Get result from async query
    public function refresh(){}//Refreshes
    public function release_savepoint(){}//Removes the named savepoint from the set of savepoints of the current transaction
    public function rollback(){}//Rolls back current transaction
    public function rpl_query_type(){}//Returns RPL query type
    public function savepoint(){}//Set a named transaction savepoint
    public function select_db(){}//Selects the default database for database queries
    public function send_query(){}//Send the query and return
    public function set_charset(){}//Sets the default client character set
    public function set_local_infile_default(){}//Unsets user defined handler for load local infile command
    public function set_local_infile_handler(){}//Set callback function for LOAD DATA LOCAL INFILE command
    public function ssl_set(){}//Used for establishing secure connections using SSL
    public function stat(){}//Gets the current system status
    public function stmt_init(){}//Initializes a statement and returns an object for use with mysqli_stmt_prepare
    public function store_result(){}//Transfers a result set from the last query
    public function thread_safe(){}//Returns whether thread safety is given or not
    public function use_result(){}//Initiate a result set retrieval
    //mysqli_stmt(){}//The mysqli_stmt c
    public function disable_reads_from_master(){}// — Disable reads from master
    public function set_opt(){}// — Alias of mysqli_options
}