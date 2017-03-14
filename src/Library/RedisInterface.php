<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 18:24
 *
 * redis接口
 *
 */
interface RedisInterface
{
    public function set($key, $value);
    public function expire($key, $timeout);
    public function del($key);
    public function get($key);
    public function keys($p);
    public function hset($key, $hash_key, $value);
    public function rpush($key, $value);
    public function hkeys($key);
    public function hgetall($key);
}