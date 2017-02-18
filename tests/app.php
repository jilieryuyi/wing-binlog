<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/8
 * Time: 10:42
 */
include "vendor/autoload.php";


use Wing\Binlog\Library\EventPublish;

$bin = new \Wing\Binlog\Library\BinLog(
    \Wing\Binlog\Library\Context::instance()->activity_pdo
);

$bin->onChange( function( $database_name, $table_name, $event_data ){

    echo "数据库：",$database_name,"\r\n";
    echo "数据表：",$table_name,"\r\n";
    echo "改变数据：";var_dump($event_data);
    echo "\r\n\r\n\r\n";

    $event = new EventPublish(
        $database_name,
        $table_name,
        $event_data
    );
    $event->trigger();

});