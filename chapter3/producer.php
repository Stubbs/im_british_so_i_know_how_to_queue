<?php

require_once('vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

$queue = 'chapter5';
$exchange = 'im_british_examples';

$connection = new AMQPConnection('localhost', 5672, 'client', 'client', '/');
$channel = $connection->channel();
$channel->queue_declare($queue, false, true, false, false);
$channel->exchange_declare($exchange, 'topic', false, true, false);
$channel->queue_bind($queue, $exchange);

$body = implode(" ", array_slice($argv, 1));
$message = new AMQPMessage($body, array('content_type' => 'text/plain', 'delivery_mode' => 2));

$channel->basic_publish($message, $exchange);

$channel->close();
$connection->close();
