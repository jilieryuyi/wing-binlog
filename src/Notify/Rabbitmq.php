<?php namespace Seals\Notify;
use Seals\Library\Context;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/12
 * Time: 07:40
 * @property AMQPStreamConnection $conn
 * @property \PhpAmqpLib\Channel\AMQPChannel $channel
 */
class Rabbitmq implements \Seals\Library\Notify
{
    private $conn;
    private $channel;
    private $exchange_name;
    private $queue_name;

    public function __construct($exchange_name, $queue_name)
    {
        $this->exchange_name = $exchange_name;
        $this->queue_name    = $queue_name;
        $this->connect();
    }

    protected function connect()
    {
        $this->conn     = null;
        $this->channel  = null;

        try {
            $this->conn = new AMQPStreamConnection(
                Context::instance()->rabbitmq_config["host"],
                Context::instance()->rabbitmq_config["port"],
                Context::instance()->rabbitmq_config["user"],
                Context::instance()->rabbitmq_config["password"],
                Context::instance()->rabbitmq_config["vhost"]
            );
            $this->channel = $this->conn->channel();
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
            if (!$this->channel) {
                Context::instance()->logger->error("rabbitmq is not connect");
                return false;
            }
            $this->channel->queue_declare($this->queue_name, false, true, false, false);
            $this->channel->exchange_declare($this->exchange_name, 'direct', false, true, false);
            $this->channel->queue_bind($this->queue_name, $this->exchange_name);

            $message = new AMQPMessage(json_encode($event_data), array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
            $this->channel->basic_publish($message, $this->exchange_name);
            return true;
        } catch (\Exception $e) {
            Context::instance()->logger->error("rabbitmq publish error => ".$e->getMessage());
            var_dump($e->getMessage());
            $this->channel->close();
            $this->conn->close();
            $this->connect();
            return false;
        }
    }
}