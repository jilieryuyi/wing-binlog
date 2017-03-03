<?php namespace Seals\Notify;
use Seals\Library\Context;
use Seals\Library\Notify;
use Seals\Library\Queue;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/18
 * Time: 10:14
 *
 * redis事件通知的实现
 *
 */
class Redis implements Notify
{

    private $queue;

    /**
     * 构造函数
     *
     * @param string $list_name
     */
    public function __construct($list_name)
    {
        $this->queue = new Queue($list_name, Context::instance()->redis);
    }

    /**
     * 发送数据
     *
     * @param array $event_data
     * @return bool
     */
    public function send(array $event_data)
    {
        $success = $this->queue->push($event_data);
        return $success;
    }
}