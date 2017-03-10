<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/18
 * Time: 10:10
 *
 * 事件通知接口实现
 *
 */
interface Notify
{
    /**
     * 发送通知
     *
     * @return bool
     */
    public function send(array $event_data);
}