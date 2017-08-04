<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/26
 * Time: 19:40
 */
class Cache
{

}
class Log
{
    public function __construct()
    {
        if (!Context::instance()->cache)
            Context::instance()->cacheInit();
    }
}
class Context
{
    private static $instance = null;
    public $log = null;
    public $cache = null;
    public static function instance()
    {
        if (!self::$instance)
            self::$instance = new self();
        return self::$instance;
    }

    public function __construct()
    {
        $this->log = new Log();
    }

    public function cacheInit()
    {
        $this->cache = new Cache();
    }
}

Context::instance();
