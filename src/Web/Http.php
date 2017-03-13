<?php namespace Seals\Web;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/13
 * Time: 11:55
 */
class Http extends Tcp
{
    const ON_HTTP_RECEIVE = "on_http_msg";

    protected $http_host;
    protected $http_port;

    public function __construct($ip = "0.0.0.0", $port = 9998)
    {
        parent::__construct($ip, $port);
        $this->on(self::ON_WRITE,   [$this, "onWrite"]);
        $this->on(self::ON_RECEIVE, [$this, "onReceive"]);
    }

    //http协议在发送后要关闭连接
    public function onWrite($client, $buffer, $id)
    {
        fclose($client);
        event_buffer_free($buffer);
        unset($this->clients[$id]);
        unset($this->buffers[$id]);
        $this->index--;
    }

    public function onReceive($client, $buffer, $id, $data)
    {
        $this->call(self::ON_HTTP_RECEIVE, [$this, $buffer, new HttpData($data), $data]);
    }

    public function send($buffer, $data)
    {
        event_buffer_write($buffer,$data);
    }
}