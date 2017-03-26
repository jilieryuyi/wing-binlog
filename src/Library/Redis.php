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
        try {
            $this->redis = null;
            $this->redis = new \Redis();
            $this->redis->connect($this->host, $this->port);
            if ($this->password) {
                $this->redis->auth($this->password);
            }
        } catch (\Exception $e) {
            Context::instance()->logger->error("redis connect error => ".$e->getMessage());
            var_dump($e->getMessage());
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
            if (is_array($value))
                $value = json_encode($value);
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
            $str = $this->redis->get($key);
            $data = @json_decode($str, true);

            if (is_array($data))
                return $data;

            return $str;
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

    public function hset($key, $hash_key, $value)
    {
        try {
            if (is_array($value))
                $value = json_encode($value);
            return $this->redis->hSet($key, $hash_key, $value);
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

    public function rpush($key, $value)
    {
        try {
            if (is_array($value))
                $value = json_encode($value);
            return $this->redis->rPush($key, $value);
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

    public function hkeys($key)
    {
        try {
            return $this->redis->hKeys($key);
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

    public function hgetall($key)
    {
        try {
            return $this->redis->hGetAll($key);
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

    public function hexists($key, $hash_key)
    {
        try {
            return $this->redis->hExists($key, $hash_key);
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

    public function hget($key, $hash_key)
    {
        try {
            return $this->redis->hGet($key, $hash_key);
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

    public function incr($key)
    {
        try {
            return $this->redis->incr($key);
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

    public function lrange($key, $start, $end) {
        try {
            return $this->redis->lRange($key, $start, $end);
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