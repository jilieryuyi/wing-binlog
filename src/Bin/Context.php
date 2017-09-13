<?php namespace Wing\Bin;
use phpDocumentor\Reflection\Types\Resource;
use Wing\Library\PDO;

/**
 * Context.php
 * User: huangxiaoan
 * Created: 2017/9/13 16:41
 * Email: huangxiaoan@xunlei.com
 */
class Context
{
	/**
	 * @var PDO
	 */
	public $pdo;

	/**
	 * @var string
	 */
	public $host;

	public $db_name;
	public $user;
	public $password;
	public $port;

	/**
	 * @var Resource $socket socket resource
	 */
	public $socket;
	/**
	 * @var bool
	 */
	public $checksum;
	public $slave_server_id;
	public $last_binlog_file = null;
	public $last_pos = 4;
}