<?php namespace Seals\Notify;
use Seals\Library\Notify;
use Seals\Library\Queue;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/18
 * Time: 10:14
 */
class Redis implements Notify {

    private $queue;
    public function __construct( $list_name )
    {
        $this->queue = new Queue($list_name);
    }

    public function send($database_name, $table_name, array $event_data)
    {
        $success = $this->queue->push([
            "database_name" => $database_name,
            "table_name"    => $table_name,
            "event_data"    => $event_data
        ]);
        return $success;
    }
}