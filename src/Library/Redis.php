<?php namespace Wing\Binlog\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/13
 * Time: 11:12
 *
 * @property  \Redis $redis
 *
 */
class Redis implements RedisInterface {

    private $redis = null;
    private $host;
    private $port;
    private $password;

    public function __construct( $host, $port, $password = null )
    {
        $this->redis = new \Redis();
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;

        $this->connect();
    }

    private function connect(){
        $this->redis->connect( $this->host, $this->port, $this->password );
        if( $this->password ){
            $this->redis->auth( $this->password );
        }
    }

    public function __call($name, $arguments)
    {
        try {
            return call_user_func_array([$this->redis, $name], $arguments);
        }catch( \Exception $e ){
            var_dump($e);
            $this->connect();
        }
        return null;
    }
}