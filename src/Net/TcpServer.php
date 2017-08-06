<?php namespace Wing\Net;
/**
 * @author yuyi
 * @created 2016/8/13 20:32
 * @email 297341015@qq.com
 */
class TcpServer extends Tcp
{
    const TCP_ON_MESSAGE = "tcp_on_message";
    const TCP_ON_CONNECT = "tcp_on_connect";
    const TCP_ON_CLOSE   = "tcp_on_close";
    public function __construct($ip = "0.0.0.0", $port = 9998)
    {
        parent::__construct($ip, $port);
        $this->on(self::ON_RECEIVE,[$this, "onMessage"]);
        $this->on(self::ON_CONNECT, [$this, "onConnect"]);
        $this->on(self::ON_CLOSE, [$this, "onClose"]);
        $this->on(self::ON_ERROR, [$this, "onClose"]);
    }


    public function onMessage($client, $buffer, $recv_msg)
    {
        $_client = new TcpClient($this,$client, $buffer);
        $this->call(self::TCP_ON_MESSAGE, [$_client, $recv_msg]);
    }

    public function onConnect($client, $buffer)
    {
        $_client = new TcpClient($this,$client, $buffer);
        $this->call(self::TCP_ON_CONNECT, [$_client]);
    }

    public function onClose($client, $buffer, $error=null)
    {
        $_client = new TcpClient($this,$client, $buffer);
        $this->call(self::TCP_ON_CLOSE, [$_client]);
    }

}