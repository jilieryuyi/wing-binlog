<?php namespace Wing\Library;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/11
 * Time: 10:22
 */
interface ICache
{
    public function set($key, $value, $timeout = 0);
    public function get($key);
    public function del($key);
    public function keys($p = ".*");
}
