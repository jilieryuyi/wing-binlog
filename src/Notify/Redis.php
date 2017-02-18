<?php namespace Wing\Notify;
use Wing\Binlog\Library\Notify;
use Wing\Binlog\Library\Queue;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/18
 * Time: 10:14
 */
class Redis implements Notify {

    const EVENT_LIST = "wing:mysqlbinlog:event:list";

    public function send($database_name, $table_name, array $event_data)
    {
        $queue   = new Queue( self::EVENT_LIST );
        $success = $queue->push([
            "database_name" => $database_name,
            "table_name"    => $table_name,
            "event_data"    => $event_data
        ]);
        return $success;
    }
}