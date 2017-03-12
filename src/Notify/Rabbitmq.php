<?php namespace Seals\Notify;
use Seals\Library\Context;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/12
 * Time: 07:40
 * @property \AMQPExchange $exchange
 */
class Rabbitmq implements \Seals\Library\Notify
{
    private $conn;
    private $channel;
    private $exchange;

    private $user;
    private $password;
    private $host;
    private $port;

    public function __construct($user, $password, $host, $port = 5672)
    {
        $this->user     = $user;
        $this->password = $password;
        $this->host     = $host;
        $this->port     = $port;

        $this->connect();
    }

    protected function connect()
    {
        $this->conn     = null;
        $this->channel  = null;
        $this->exchange = null;

        try {
            $this->conn = new \AMQPConnection([
                'host' => $this->host,
                'port' => $this->port,
                'login' => $this->user,
                'password' => $this->password
            ]);
            $this->channel  = new \AMQPChannel($this->conn);
            $this->exchange = new \AMQPExchange($this->channel);
            $this->exchange->setName('exchange');
            $this->exchange->setType(AMQP_EX_TYPE_DIRECT);
            $this->exchange->setFlags(AMQP_DURABLE);
            $this->exchange->declareExchange();
        } catch (\Exception $e) {
            Context::instance()->logger->error("rabbitmq connect error => ".$e->getMessage());
            var_dump($e->getMessage());
        }
    }

    /**
     * 发送消息到mq队列
     *
     * @return bool
     */
    public function send(array $event_data)
    {
        try {
            return $this->exchange->publish(json_encode($event_data), "yuyi");
        } catch (\Exception $e) {
            Context::instance()->logger->error("rabbitmq publish error => ".$e->getMessage());
            var_dump($e->getMessage());
            $this->connect();
            return false;
        }
    }
}