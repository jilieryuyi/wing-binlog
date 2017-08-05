<?php namespace Seals\Cache;
use Seals\Library\CacheInterface;
use Seals\Library\Context;
use Seals\Library\RedisInterface;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/11
 * Time: 20:18
 */
class Redis implements CacheInterface
{
    private $redis;
    public function __construct(RedisInterface $redis)
    {
        $this->redis = $redis;
    }

    public function set($key, $value, $timeout = 0)
    {
        if (is_array($value))
            $value = json_encode($value);
        $success = $this->redis->set($key, $value);
        if ($timeout>0)
            $this->redis->expire($key, $timeout);
        return $success;
    }
    public function get($key)
    {
        $data = $this->redis->get($key);

//        $_data = @@json_decode($data, true);
//
//        if (is_array($_data))
//            return $_data;

        return $data;

    }
    public function del($key)
    {
        return $this->redis->del($key);
    }
    public function keys($p = "*")
    {
        return $this->redis->keys($p);
    }
}