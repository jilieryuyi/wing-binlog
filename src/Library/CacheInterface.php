<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/11
 * Time: 10:22
 */
interface CacheInterface
{
    public function set($key, $value, $timeout = -1);
    public function get($key);
    public function del($key);
    public function keys($p = ".*");
}