<?php namespace Seals\Cache;
use Seals\Library\CacheInterface;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/11
 * Time: 10:22
 */
class File implements CacheInterface
{
    protected $cache_dir;
    public function __construct($cache_dir)
    {
        $this->cache_dir = $cache_dir;
    }
    public function set($key, $value, $timeout = -1)
    {
        // TODO: Implement set() method.
    }
    public function get($key)
    {
        // TODO: Implement get() method.
    }
    public function del($key)
    {
        // TODO: Implement del() method.
    }
}