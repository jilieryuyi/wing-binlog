<?php namespace Wing\Library\Mysql;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/20
 * Time: 15:31
 */
class PDO
{
    public $affected_rows;
    public $client_info;//Get MySQL client info
    public $client_version;//(){}//Returns the MySQL client version as a number
    public $connect_errno;//(){}//Returns the error code from last connect call
    public $connect_error;//(){}//Returns a string description of the last connect error
    public $errno;//(){}//Returns the error code for the most recent function call
    public $error_list;//(){}//Returns a list of errors from the last command executed
    public $error;//(){}//Returns a string description of the last error
    public $field_count;//(){}//Returns the number of columns for the most recent query
    public $host_info;//(){}//Returns a string representing the type of connection used
    public $protocol_version;//(){}//Returns the version of the MySQL protocol used
    public $server_info;//(){}//Returns the version of the MySQL server
    public $server_version;//(){}//Returns the version of the MySQL server as an integer
    public $info;//(){}//Retrieves information about the most recently executed query
    public $insert_id;//(){}//Returns the auto generated id used in the latest query
    public $sqlstate;//(){}//Returns the SQLSTATE error from previous MySQL operation
    public $thread_id;//(){}//Returns the thread ID for the current connection
    public $warning_count;//(){}//Returns the number of warnings from the last query for the given link

    //Open a new connection to the MySQL server
    public function __construct($host, $username, $passwd, $dbname, $port = 3306)
    {
        $context        = new \Wing\Bin\Context();
        $pdo            = new \Wing\Library\PDO();

        $context->pdo       = \Wing\Bin\Db::$pdo = $pdo;
        $context->host      = $host;//$mysql_config["mysql"]["host"];
        $context->db_name   = $dbname;//$mysql_config["mysql"]["db_name"];
        $context->user      = $username;//$mysql_config["mysql"]["user"];
        $context->password  = $passwd;//$mysql_config["mysql"]["password"];
        $context->port      = $port;//$mysql_config["mysql"]["port"];
        $context->checksum  = !!\Wing\Bin\Db::getChecksum();

        $context->slave_server_id   = 9999;//$mysql_config["slave_server_id"];
        $context->last_binlog_file  = null;
        $context->last_pos          = 0;

        //认证
        \Wing\Bin\Auth\Auth::execute($context);
    }

    public function autocommit()
    {
        
    }
    public function begin_transaction(){
        
    }//(){}//Starts a transaction
    public function change_user(){}//(){}//Changes the user of the specified database connection
    public function character_set_name(){}//Returns the default character set for the database connection
    public function close(){}//Closes a previously opened database connection
    public function commit(){}//Commits the current transaction
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
    public function real_escape_string(){}//Escapes special characters in a string for use in an SQL statement, taking into account the current charset of the connection
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