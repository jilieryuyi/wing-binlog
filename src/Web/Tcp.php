<?php namespace Seals\Web;
use Seals\Library\Context;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 07:46
 *
 * php tcp class
 * support libevent and select mode
 *
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
    const ON_CLOSE   = "on_close";

    protected $callbacks = [];

    protected $ip;
    protected $port;
    protected $error_times     = 0;
    protected $send_fail_times = 0;
    protected $accept_times    = 0;
    protected $start_time      = 0;

    /**
     * construct
     *
     * @param string $ip
     * @param int $port
     */
    public function __construct($ip = "0.0.0.0", $port = 9998)
    {
        $this->ip         = $ip;
        $this->port       = $port;
        $this->start_time = time();

        $context      = stream_context_create(["socket" => ["so_reuseport" => 1]]);
        $this->socket = stream_socket_server(
            'tcp://' . $this->ip . ':' . $this->port,
            $errno,
            $errstr,
            STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
            $context
        );

        if (!$this->socket) {
            var_dump($errno,$errstr);
            die("create socket error => ip:" . $this->ip . " port:" . $this->port."\r\n");
        }

        stream_set_blocking($this->socket, 0);

        if (!function_exists("event_base_new")) {
            $this->clients[] = $this->socket;
            $this->index++;
        }

    }

    /**
     * on receive data, this will be call
     *
     * @param resource $client
     * @param resource $buffer
     * @param int $index
     * @params string $data
     */
    protected function onReceive($client, $buffer, $index, $data)
    {
        $this->call(self::ON_RECEIVE,[$client, $buffer, $index, $data]);
    }

    /**
     * this func will be call on send
     *
     * @param resource $client
     * @param resource $buffer
     * @param int $index
     */
    protected function onWrite($client, $buffer, $index = 0)
    {
        if (is_array($buffer)) {
            $index  = $buffer[1];
            $buffer = $buffer[0];
        }
        $this->call(self::ON_WRITE,[$client, $buffer, $index]);
    }

    /**
     * this func will be call on connect
     *
     * @param resource $client
     * @param resource $buffer
     * @param int $index
     */
    protected function onConnect($client, $buffer, $index)
    {
        $this->accept_times++;
        $this->call(self::ON_CONNECT, [$client, $buffer, $index]);
    }

    /**
     * this func will be call on close
     *
     * @param resource $client
     * @param resource $buffer
     * @param int $index
     */
    protected function onClose($client, $buffer, $index)
    {
        $this->call(self::ON_CLOSE,[$client, $buffer, $index]);
    }

    /**
     * this func will be call on error happened
     *
     * @param resource $client
     * @param resource $buffer
     * @param int $index
     */
    protected function onError($client, $buffer, $index, $error)
    {
        $this->error_times++;
        $this->call(self::ON_ERROR, [$client, $buffer, $index, $error]);
    }


    /**
     * start tcp server
     */
    public function start()
    {
        //if libevent support, then use it
        if (function_exists("event_base_new")) {
            $base  = event_base_new();
            $event = event_new();

            event_set($event, $this->socket, EV_READ | EV_PERSIST, [$this, 'accept'], $base);
            event_base_set($event, $base);
            event_add($event);
            event_base_loop($base);
        } else {
            //if not, use the socket select mode
            $_w = $_e = null;
            while (1) {
                $read   = $this->clients;
                $mod_fd = stream_select($read, $_w, $_e, 5);

                if ($mod_fd === false) {
                    break;
                }

                if ($mod_fd === 0) {
                    continue;
                }

                foreach ($read as $client) {
                    if (!$client) {
                        continue;
                    }
                    if ($client === $this->socket) {
                        //on connect
                        $conn = stream_socket_accept($this->socket);
                        if (!$conn) {
                            continue;
                        }
                        stream_set_blocking($conn, 0);
                        $this->onConnect($conn, null, $this->index);
                        $this->clients[] = $conn;
                        $this->index++;
                    } else {
                        echo "read\r\n";
                        $sock_data = fread($client, 10240);
                        if ($sock_data === FALSE) {

                            $key_to_del = array_search($client, $this->clients, TRUE);
                            if ($key_to_del === false)
                                continue;

                            $this->onError($client, null, $key_to_del, "");
                            fclose($client);
                            unset($this->clients[$key_to_del]);
                            $this->index--;
                        }
                        elseif (strlen($sock_data) === 0) {
                            //connection closed
                            $key_to_del = array_search($client, $this->clients, true);
                            $this->onClose($client, null, $key_to_del);
                            fclose($client);
                            unset($this->clients[$key_to_del]);
                            $this->index--;
                        } else {
                            var_dump($sock_data);
                            $this->onReceive($client, null, array_search($client, $this->clients, TRUE), $sock_data);
                        }
                    }
                }
            }
        }
    }

    /**
     * send data to a client socket
     *
     * @param resource $socket
     * @param string $data
     *
     * @return int
     */
    public function sendSocket($socket, $data)
    {
        $byte = 0;
        try {
            $byte = fwrite($socket, $data);
        } catch(\Exception $e) {
            $byte = 0;
            Context::instance()->logger->error($e->getMessage());
        }
        $this->onWrite($socket, null, array_search($socket, $this->clients, true));
        return $byte;
    }

    /**
     * bind event callback
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
     * call event callback
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
     * accept, only libevent
     *
     * @param resource $socket
     * @param mixed $flag
     * @param resource $base
     * @return bool
     */
    protected function accept($socket, $flag, $base)
    {
        try {
            if (!$socket) {
                return false;
            }
            $connection = @stream_socket_accept($socket);
            if (!$connection) {
                return false;
            }
            stream_set_blocking($connection, 0);

            $buffer = event_buffer_new($connection, [$this, 'read'], [$this, 'onWrite'], [$this, 'error'], [$connection, $this->index, 123]);
            if (!$buffer && !is_resource($buffer)) {
                return false;
            }
            event_buffer_base_set($buffer, $base);
            event_buffer_timeout_set($buffer, 30, 30);
            event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
            event_buffer_priority_set($buffer, 10);
            event_buffer_enable($buffer, EV_READ | EV_PERSIST);

            $this->clients[$this->index] = $connection;
            $this->buffers[$this->index] = $buffer;

            $this->onConnect($connection, $buffer, $this->index);
            $this->index++;
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * error happened,only libevent
     *
     * @param resource $buffer
     * @param string $error
     * @param array $params
     */
    protected function error($buffer, $error, $params)
    {
        echo "send error free\r\n";

        event_buffer_disable($buffer, EV_READ | EV_WRITE);
        event_buffer_free($buffer);

        $this->onError($params[0], $buffer, $params[1], $error);
        fclose($params[0]);
        unset($this->clients[$params[1]], $this->buffers[$params[1]]);
        $this->index--;
    }

    /**
     * libevent read wait
     *
     * @param resource $buffer
     * @param array $params 0 is the socket client 1 is the int index
     */
    protected function read($buffer, $params)
    {
        while ($read = event_buffer_read($buffer, 10240)) {
            var_dump($read);
            $this->onReceive($params[0], $buffer, $params[1], $read);
        }
        $this->onClose($params[0], $buffer, $params[1]);
    }

    /**
     * print debug info
     */
    public function debug()
    {
        $s = 0;
        if (time() > $this->start_time)
            $s = $this->accept_times/(time()-$this->start_time);
        echo "请求次数/失败次数/发送失败/每秒处理 ==> ".$this->accept_times."/".$this->error_times."/".$this->send_fail_times."/".$s."\r\n";
        echo "当前连接数",count($this->clients),"-buffers数量",count($this->buffers),"\r\n";
    }
}