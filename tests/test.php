<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/10
 * Time: 10:31
 */
include __DIR__."/../vendor/autoload.php";

$bin = new \Wing\Binlog\Library\BinLog(
    new \Wing\Binlog\Library\PDO("root","123456","localhost","ylb_activity")
);
//
//$data = $bin->getCurrentLogInfo();
//
//var_dump($data);
//
//$file = $bin->getCurrentLogFile();
//echo $file,"\r\n";
//
//$data = $bin->getFiles();
//
//var_dump($data);
//
//$data = $bin->getLogs();
//
//var_dump($data);
//

$bin = new \Wing\Binlog\Library\BinLog(
    new \Wing\Binlog\Library\PDO("root","123456","localhost","ylb_activity")
);
$bin->onChange(function( $database_name, $table_name, $event_data ){

    echo "数据库：",$database_name,"\r\n";
    echo "数据表：",$table_name,"\r\n";
    echo "改变数据：";var_dump($event_data);
    echo "\r\n\r\n\r\n";

});

//$command = "mysqlbinlog --base64-output=DECODE-ROWS -v --start-position=4 --stop-position=8000 mysql-bin.000005";// >d:\1.sql
//var_dump((new \Wing\Binlog\Library\Command($command))->run());

//echo date("Y-m-d H:i:s",strtotime("170210 13:58:06"));