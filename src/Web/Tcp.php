<?php namespace Seals\Web;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 07:46
 */
class Tcp
{
    protected $socket;
    protected $clients = [];
    protected $buffers = [];
    protected $index   = 0;

    const ON_RECEIVE = "on_read";
    const ON_WRITE   = "on_write";
    const ON_CONNECT = "on_connect";
    const ON_ERROR   = "on_error";

    protected $callbacks = [];

    protected $ip;
    protected $port;

    /**
     * 构造函数
     *
     * @param string $ip
     * @param int $port
     */
    public function __construct($ip = "0.0.0.0", $port = 9998)
    {
        $this->ip   = $ip;
        $this->port = $port;
    }

    /**
     * 入口api
     */
    public function start()
    {
        $this->socket = stream_socket_server('tcp://'.$this->ip.':'.$this->port, $errno, $errstr);

        if (!$this->socket) {
            die("create socket error => ip:".$this->ip." port:".$this->port);
        }

        stream_set_blocking($this->socket, 0);

        $base  = event_base_new();
        $event = event_new();

        event_set($event, $this->socket, EV_READ | EV_PERSIST, [$this, 'accept'], $base);
        event_base_set($event, $base);
        event_add($event);
        event_base_loop($base);
    }

    /**
     * 绑定事件
     *
     * @param string $event
     * @param \Closure|array $callback
     */
    public function on($event, $callback)
    {
        if (!isset($this->callbacks[$event]))
            $this->callbacks[$event] = [];
        $this->callbacks[$event][] = $callback;
    }

    /**
     * 执行回调
     *
     * @param string $event
     * @param array $params
     */
    protected function call($event, array $params = [])
    {
        if (!isset($this->callbacks[$event])) {
            return;
        }

        if (!is_array($this->callbacks[$event])) {
            return;
        }

        if (count($this->callbacks[$event]) <= 0) {
            return;
        }

        foreach ($this->callbacks[$event] as $callback) {
            if (is_callable($callback)) {
                call_user_func_array($callback,$params);
            }
        }
    }

    /**
     * 析构函数
     */
    public function __destruct()
    {
        fclose($this->socket);
    }

    /**
     * 新的连接进来时的回调函数
     *
     * @param resource $socket
     * @param mixed $flag
     * @param resource $base
     */
    public function accept($socket, $flag, $base)
    {
        $connection = stream_socket_accept($socket);
        stream_set_blocking($connection, 0);

        $buffer = event_buffer_new($connection, [$this, 'read'], [$this, 'write'], [$this, 'error'], [$connection,$this->index]);

        event_buffer_base_set($buffer, $base);
        event_buffer_timeout_set($buffer, 30, 30);
        event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
        event_buffer_priority_set($buffer, 10);
        event_buffer_enable($buffer, EV_READ | EV_PERSIST);

        $this->clients[$this->index] = $connection;
        $this->buffers[$this->index] = $buffer;

        $this->call(self::ON_CONNECT, [$connection, $buffer, $this->index]);

        $this->index++;
    }

    /**
     * 错误回调函数
     */
    public function error($buffer, $error, $params)
    {
        event_buffer_disable($buffer, EV_READ | EV_WRITE);
        event_buffer_free($buffer);

        $this->call(self::ON_ERROR, [$params[0], $buffer, $params[1], $error]);

        fclose($params[0]);
        unset($this->clients[$params[1]], $this->buffers[$params[1]]);
        $this->index--;
    }

    /**
     * 收到消息时的回调函数
     */
    public function read($buffer, $params)
    {
        while ($read = event_buffer_read($buffer, 10240)) {
            var_dump($read);
            $this->call(self::ON_RECEIVE,[$params[0], $buffer, $params[1], $read]);
        }
    }

    /**
     * 发送成功时的回调函数
     */
    public function write($buffer, $params)
    {
        $this->call(self::ON_WRITE,[$params[0], $buffer, $params[1]]);
    }
}