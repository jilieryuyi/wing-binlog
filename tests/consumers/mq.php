<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/12
 * Time: 07:40
 */
include __DIR__."/../../vendor/autoload.php";
use PhpAmqpLib\Connection\AMQPStreamConnection;

$exchange    = 'wing-binlog-exchange';
$queue       = 'wing-binlog-queue';
$consumerTag = 'consumer';

$connection = new AMQPStreamConnection("127.0.0.1", 5672, "guest", "guest", "/");

$channel = $connection->channel();

$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);
$channel->basic_consume($queue, $consumerTag, false, false, false, false, function(\PhpAmqpLib\Message\AMQPMessage $message)
{
    echo "\n--------\n";
    echo $message->body;
    echo "\n--------\n";

    $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);

    // Send a message with the string "quit" to cancel the consumer.
    if ($message->body === 'quit') {
        $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
    }
});

register_shutdown_function(function(\PhpAmqpLib\Channel\AMQPChannel $channel, \PhpAmqpLib\Connection\AbstractConnection $connection)
{
    $channel->close();
    $connection->close();
}, $channel, $connection);

// Loop as long as the channel has callbacks registered
while (count($channel->callbacks)) {
    $channel->wait();
}
