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

try {
	$mysql_config   = load_config("app");
	/*$context        = new \Wing\Bin\Context();
	$pdo            = new \Wing\Library\PDO();

	$context->pdo       = \Wing\Bin\Db::$pdo = $pdo;
	$context->host      = $mysql_config["mysql"]["host"];
	$context->db_name   = $mysql_config["mysql"]["db_name"];
	$context->user      = $mysql_config["mysql"]["user"];
	$context->password  = $mysql_config["mysql"]["password"];
	$context->port      = $mysql_config["mysql"]["port"];
	$context->checksum  = !!\Wing\Bin\Db::getChecksum();

	$context->slave_server_id   = $mysql_config["slave_server_id"];
	$context->last_binlog_file  = null;
	$context->last_pos          = 0;

    //认证
	\Wing\Bin\Auth\Auth::execute($context);

    $res = \Wing\Bin\Mysql::execute(
        //'INSERT INTO xsl.`x_logs`(`id`,`module_name`,`message`) VALUES (999998, "test","test")');//
    'select * from wp_posts where id=?', [12]);

    var_dump($res);*/
	\Wing\Bin\Mysql::$debug = true;
	$pdo = new \Wing\Library\Mysql\PDO(
		$mysql_config["mysql"]["host"],
		$mysql_config["mysql"]["user"],
		$mysql_config["mysql"]["password"],
		$mysql_config["mysql"]["db_name"],
		$mysql_config["mysql"]["port"]
		);
	//test ok
	echo $pdo->character_set_name(), "\r\n";

	//close 后，后面再执行sql相关的东西，直接抛出异常了，说明关闭正常
	//\Wing\Bin\Mysql::close();



	//预处理查询 ok
	var_dump(\Wing\Bin\Mysql::execute('select * from wp_posts where id=?', [12]));


	//test ok
//	$pdo->autocommit(false);
//	//设置automit false之后，后面查询的值为0，设置为true以后，后面查询的值为1，说明正确
//	var_dump(\Wing\Bin\Mysql::query('select @@autocommit'));


	//开启事务
	//var_dump($pdo->begin_transaction(
//		\Wing\Bin\Constant\Trans::WITH_CONSISTENT_SNAPSHOT |
//		\Wing\Bin\Constant\Trans::READ_ONLY|
//		\Wing\Bin\Constant\Trans::READ_WRITE
	//));

} catch (\Exception $e) {
	var_dump($e);
}

echo "\r\nend\r\n";