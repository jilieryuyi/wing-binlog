<?php namespace Wing\Binlog\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/11
 * Time: 07:51
 */
interface Event{
    /**
     * @事件触发
     *
     * @return bool
     */
    public function trigger();
}