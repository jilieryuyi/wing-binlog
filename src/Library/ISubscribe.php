<?php namespace Wing\Library;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/4
 * Time: 22:54
 */
interface ISubscribe
{
    /**
     * @param array $config
     */
    public function __construct($config);
    /**
     * @param array $event
     */
    public function onchange($event);
}
