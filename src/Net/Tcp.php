<?php namespace Wing\Net;

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
    protected $debug = false;

    const ON_RECEIVE = "_on_read";
    const ON_WRITE   = "_on_write";
    const ON_CONNECT = "_on_connect";
    const ON_ERROR   = "_on_error";
    const ON_CLOSE   = "_on_close";
    const ON_TICK    = "_on_tick";

    protected $callbacks = [];

    protected $ip;
    protected $port;
    protected $error_times     = 0;
    protected $send_fail_times = 0;
    protected $accept_times    = 0;
    protected $start_time      = 0;

    protected $is_epoll = false;

    private $use_libevent = false;

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
            die("create socket error => ip:" . $this->ip . " port:" . $this->port."\r\n");
        }

        stream_set_blocking($this->socket, 0);
        //function_exists("event_base_new")
        if (!$this->use_libevent) {
            $this->clients[] = $this->socket;
        } else {
            $this->is_epoll = true;
        }

    }


    public function setDebug($debug)
    {
        $this->debug = !!$debug;
    }

    /**
     * on receive data, this will be call
     *
     * @param resource $client
     * @param resource $buffer
     * @params string $data
     */
    protected function onReceive($client, $buffer, $data)
    {
        $this->call(self::ON_RECEIVE,[$client, $buffer, $data]);
    }

    /**
     * this func will be call on send
     *
     * @param resource $client
     * @param resource $buffer 是event_buffer_new的返回值
     */
    protected function onWrite($buffer, $client = null)
    {
        $this->call(self::ON_WRITE,[$client, $buffer]);
    }

    /**
     * this func will be call on connect
     *
     * @param resource $client
     * @param resource $buffer
     */
    protected function onConnect($client, $buffer)
    {
        $this->accept_times++;
        $this->call(self::ON_CONNECT, [$client, $buffer]);
    }

    /**
     * this func will be call on close
     *
     * @param resource $client
     * @param resource $buffer
     */
    protected function onClose($client, $buffer)
    {
        $this->call(self::ON_CLOSE,[$client, $buffer]);
    }

    /**
     * this func will be call on error happened
     *
     * @param resource $client
     * @param resource $buffer
     */
    protected function onError($client, $buffer, $error)
    {
        $this->error_times++;
        $this->call(self::ON_ERROR, [$client, $buffer, $error]);
    }


    /**
     * start tcp server
     */
    public function start()
    {
        //if libevent support, then use it
        //function_exists("event_base_new")
        if ($this->use_libevent) {
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
                //pcntl_signal_dispatch();
                $this->call(self::ON_TICK);
                //echo "select process \r\n";
                $read   = $this->clients;
                $mod_fd = stream_select($read, $_w, $_e, 1);

                if ($mod_fd === false) {
                    break;
                }

                if ($mod_fd === 0) {
                    usleep(1000);
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
                        $this->onConnect($conn, null);
                        $this->clients[] = $conn;
                    } else {
                        $sock_data = fread($client, 10240);
                        if ($sock_data === FALSE) {

                            $key_to_del = array_search($client, $this->clients, TRUE);
                            if ($key_to_del === false)
                                continue;

                            $this->onError($client, null, "");
                            fclose($client);
                            unset($this->clients[$key_to_del]);
                        }
                        elseif (strlen($sock_data) === 0) {
                            //connection closed
                            $key_to_del = array_search($client, $this->clients, true);
                            $this->onClose($client, null);
                            fclose($client);
                            unset($this->clients[$key_to_del]);
                        } else {
                            $this->onReceive($client, null, $sock_data);
                        }
                    }
                }
            }
        }
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

            $buffer = event_buffer_new($connection, [$this, 'read'], [$this, 'onWrite'], [$this, 'error'], $connection);
            if (!$buffer && !is_resource($buffer)) {
                return false;
            }
            event_buffer_base_set($buffer, $base);
            event_buffer_timeout_set($buffer, 30, 30);
            event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
            event_buffer_priority_set($buffer, 10);
            event_buffer_enable($buffer, EV_READ | EV_PERSIST);

            $this->clients[] = $connection;
            $this->buffers[] = $buffer;

            $this->onConnect($connection, $buffer);
        } catch(\Exception $e) {
            return false;
        }
        return true;
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
            var_dump($e->getMessage());
        }
        $this->onWrite(null, $socket);
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
     * error happened,only libevent
     *
     * @param resource $buffer
     * @param string $error
     * @param array $params
     */
    protected function error($buffer, $error)
    {
        wing_log("发生错误了");
        var_dump($buffer, $error);
        event_buffer_disable($buffer, EV_READ | EV_WRITE);
        event_buffer_free($buffer);

        $i = array_search($buffer, $this->buffers);
        $this->onError($this->clients[$i], $buffer, $error);
        fclose($this->clients[$i]);
        unset($this->clients[$i], $this->buffers[$i]);
    }

    /**
     * libevent read wait
     *
     * @param resource $buffer
     */
    protected function read($buffer)
    {
        $i = array_search($buffer, $this->buffers);
        while ($read = event_buffer_read($buffer, 10240)) {
            $this->onReceive($this->clients[$i], $buffer, $read);
        }
        wing_log("tcp读取错误，客户端断开连接");
        $this->onClose($this->clients[$i], $buffer);
    }

    /**
     * print debug info
     */
    public function debug()
    {
        if ($this->debug) {
            $s = 0;
            if (time() > $this->start_time)
                $s = $this->accept_times / (time() - $this->start_time);
            wing_log("请求次数/失败次数/发送失败/每秒处理 ==> " . $this->accept_times . "/" . $this->error_times . "/" . $this->send_fail_times . "/" . $s );
            wing_log("当前连接数", count($this->clients), "-buffers数量", count($this->buffers));
        }
    }

    public function send($buffer, $data, $client)
    {
        if ($buffer) {
            $success = event_buffer_write($buffer,$data);
        }
        else
            $success = $this->sendSocket($client, $data);
        if (!$success) {
            $this->send_fail_times++;
            $i = array_search($client, $this->clients);
            fclose($client);
            if ($buffer) {
                event_buffer_free($buffer);
                unset($this->buffers[$i]);
            }
            unset($this->clients[$i]);
        }
        return $success;
    }

}