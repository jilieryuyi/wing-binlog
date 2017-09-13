<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/9/9
 * Time: 07:02
 */
include __DIR__."/../vendor/autoload.php";
define("HOME", dirname(__DIR__));

//初始化一些系统常量
define("WINDOWS", "windows");
define("LINUX", "linux");

//定义时间区
if(!date_default_timezone_get() || !ini_get("date.timezone")) {
    date_default_timezone_set("PRC");
}

define("WING_DEBUG", true);
$start = time();


$mysql_config 	= load_config("add");
$context		= new \Wing\Bin\Context();
$pdo			= new \Wing\Library\PDO();

$context->pdo 		= \Wing\Bin\Db::$pdo = $pdo;
$context->checksum 	= !!\Wing\Bin\Db::getChecksum();
$context->host 		= $mysql_config["mysql"]["host"];
$context->db_name 	= $mysql_config["mysql"]["db_name"];
$context->user		= $mysql_config["mysql"]["user"];
$context->password 	= $mysql_config["mysql"]["password"];
$context->password 	= $mysql_config["mysql"]["port"];

$context->slave_server_id = $mysql_config["slave_server_id"];

\Wing\Bin\Auth\Auth::execute($context);







$times = 0;
$slave = new \Wing\Library\Slave();

while(1){
    $result = $slave->getEvent();
    if ($result) {
        var_dump($result);
        $times+=count($result["event"]["data"]);
        $s = time()-$start;
        if ($s>0)
        echo $times,"次，",$times/($s)."/次事件每秒，耗时",$s,"秒\r\n";
    }
}