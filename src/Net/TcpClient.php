<?php namespace Wing\Net;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/8/6
 * Time: 18:16
 *
 * @property Tcp $tcp
 */
class TcpClient
{
    private $tcp;
    private $client;
    private $buffer;
    public function __construct($tcp, $client, $buffer)
    {
        $this->tcp = $tcp;
        $this->client = $client;
        $this->buffer = $buffer;
    }

    public function send($msg)
    {
        return $this->tcp->send($this->buffer, $msg, $this->client);
    }
}