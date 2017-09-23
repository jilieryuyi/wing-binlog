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

//认证
\Wing\Bin\Auth\Auth::execute(
	$mysql_config["mysql"]["host"],
	$mysql_config["mysql"]["user"],
	$mysql_config["mysql"]["password"], $mysql_config["mysql"]["db_name"],
	$mysql_config["mysql"]["port"]
);

$binlog = new \Wing\Bin\BinLog(
	null,
	0,
	!!\Wing\Bin\Db::getChecksum(),
	$mysql_config["slave_server_id"]
);

$times = 0;

//binlog事件监听
while(1){
    $result = $binlog->getEvent();//\Wing\Bin\Binlog::getEvent();
    if ($result) {
        var_dump($result);
        $times+=count($result["event"]["data"]);
        $s = time()-$start;
        if ($s>0)
        echo $times,"次，",$times/($s)."/次事件每秒，耗时",$s,"秒\r\n";
    }
}