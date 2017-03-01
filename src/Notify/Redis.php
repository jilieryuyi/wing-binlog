<?php namespace Seals\Notify;
use Seals\Library\Context;
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
        $this->queue = new Queue( $list_name, Context::instance()->redis );
    }

    public function send( array $event_data)
    {
        $success = $this->queue->push($event_data);
        return $success;
    }
}