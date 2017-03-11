<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/13
 * Time: 11:12
 *
 * redis实现
 *
 * @property  \Redis $redis
 *
 */
class Redis implements RedisInterface
{

    private $redis = null;
    private $host;
    private $port;
    private $password;

    /**
     * 构造函数
     *
     * @param string $host
     * @param int $port
     * @param string $password
     */
    public function __construct($host, $port, $password = null)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->password = $password;

        $this->connect();
    }

    /**
     * 连接redis
     */
    private function connect()
    {
        $this->redis    = null;
        $this->redis    = new \Redis();
        $this->redis->connect($this->host, $this->port);
        if ($this->password) {
            $this->redis->auth($this->password);
        }
    }

    /**
     * 魔术方法
     */
    public function __call($name, $arguments)
    {
        try {
            return call_user_func_array([$this->redis, $name], $arguments);
        } catch (\Exception $e) {
            echo $name,"=>",var_dump($arguments);
            trigger_error("call ".$name." with params : ".
                json_encode($arguments,JSON_UNESCAPED_UNICODE).", error happened :".
                $e->getMessage()
            );

            var_dump($e->getMessage());
            $this->connect();
        }
        return null;
    }

    public function set($key, $value)
    {
        try {
            return $this->redis->set($key, $value);
        } catch (\Exception $e) {
            echo __FUNCTION__,"=>",var_dump(func_get_args());
            trigger_error("call ".__FUNCTION__." with params : ".
                json_encode(func_get_args(),JSON_UNESCAPED_UNICODE).", error happened :".
                $e->getMessage()
            );

            var_dump($e->getMessage());
            $this->connect();
            return false;
        }
    }
    public function expire($key, $timeout)
    {
        try {
            return $this->redis->expire($key, $timeout);
        } catch (\Exception $e) {
            echo __FUNCTION__,"=>",var_dump(func_get_args());
            trigger_error("call ".__FUNCTION__." with params : ".
                json_encode(func_get_args(),JSON_UNESCAPED_UNICODE).", error happened :".
                $e->getMessage()
            );

            var_dump($e->getMessage());
            $this->connect();
            return false;
        }

    }
    public function del($key)
    {
        try {
            return $this->redis->del($key);
        } catch (\Exception $e) {
            echo __FUNCTION__,"=>",var_dump(func_get_args());
            trigger_error("call ".__FUNCTION__." with params : ".
                json_encode(func_get_args(),JSON_UNESCAPED_UNICODE).", error happened :".
                $e->getMessage()
            );

            var_dump($e->getMessage());
            $this->connect();
            return 0;
        }
    }
    public function get($key)
    {
        try {
            return $this->redis->get($key);
        } catch (\Exception $e) {
            echo __FUNCTION__,"=>",var_dump(func_get_args());
            trigger_error("call ".__FUNCTION__." with params : ".
                json_encode(func_get_args(),JSON_UNESCAPED_UNICODE).", error happened :".
                $e->getMessage()
            );

            var_dump($e->getMessage());
            $this->connect();
            return null;
        }
    }
    public function keys($p)
    {
        try {
            return $this->redis->keys($p);
        } catch (\Exception $e) {
            echo __FUNCTION__,"=>",var_dump(func_get_args());
            trigger_error("call ".__FUNCTION__." with params : ".
                json_encode(func_get_args(),JSON_UNESCAPED_UNICODE).", error happened :".
                $e->getMessage()
            );

            var_dump($e->getMessage());
            $this->connect();
            return null;
        }
    }
}