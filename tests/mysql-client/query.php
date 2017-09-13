<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 07:02
 */
include __DIR__."/../../vendor/autoload.php";
define("HOME", dirname(dirname(__DIR__)));

//初始化一些系统常量
define("WINDOWS", "windows");
define("LINUX", "linux");

//定义时间区
if(!date_default_timezone_get() || !ini_get("date.timezone")) {
    date_default_timezone_set("PRC");
}

define("WING_DEBUG", true);
$start = time();


$mysql_config 	= load_config("app");
$context		= new \Wing\Bin\Context();
$pdo			= new \Wing\Library\PDO();

$context->pdo 		= \Wing\Bin\Db::$pdo = $pdo;
$context->host 		= $mysql_config["mysql"]["host"];
$context->db_name 	= $mysql_config["mysql"]["db_name"];
$context->user		= $mysql_config["mysql"]["user"];
$context->password 	= $mysql_config["mysql"]["password"];
$context->port	 	= $mysql_config["mysql"]["port"];
$context->checksum  = !!\Wing\Bin\Db::getChecksum();

$context->slave_server_id 	= $mysql_config["slave_server_id"];
$context->last_binlog_file 	= null;
$context->last_pos 			= 0;

//认证
\Wing\Bin\Auth\Auth::execute($context);

$res = \Wing\Bin\Mysql::query('SELECT * FROM `wp_posts` WHERE 1 limit 2');
var_dump($res);
