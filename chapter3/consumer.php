<?php

require_once('./vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPConnection;

$queue = 'chapter3';
$exchange = 'im_british_examples';

$connection = new AMQPConnection('localhost', 5672, 'client', 'client', '/');
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, 'direct', false, true, false);
$channel->queue_bind($queue, $exchange);

$channel->basic_consume(
    $queue,
    'chapter3_consumer',
    false,
    true,
    false,
    false,
    function ($msg) {
        echo $msg->body . "\n";
    }
);

function shutdown($channel, $exchange)
{
    $channel->close();
    $exchange->close();
}

register_shutdown_function('shutdown', $channel, $exchange);

while(count($channel->callbacks)) {
    $channel->wait();
}
