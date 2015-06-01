<?php

namespace ImBritish;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;

use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;

class PourCommand extends Command
{
    private $exchange = 'im_british';

    protected function configure()
    {
        $this
            ->setName('pour')
            ->setDescription('Add a bunch of random messages to a queue..')
            ->addArgument('queue', InputArgument::REQUIRED, 'The routing key you want to pour messages .')
            ->addArgument('num', InputArgument::OPTIONAL, 'The number of messages to add to the queue.', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queue = $input->getArgument('queue');

        $output->writeln("<info>Adding " . $input->getArgument('num') . " messages to queue " . $this->queue);

        $connection = new AMQPConnection('localhost', 5672, 'client', 'client', '/');
        $channel = $connection->channel();
        $channel->queue_declare($this->queue, false, true, false, false);
        $channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $channel->queue_bind($this->queue, $this->exchange, $input->getArgument('queue'));

        $msg = new AMQPMessage(
            "{
                'id': 0,
                'call_start': 'timestamp',
                'call_end': 'timestamp',
                'duration': 110,
                'destination': 216
            }",
            array('content_type' => 'text/plain', 'delivery_mode' => 2)
        );


        for ($i=0; $i < $input->getArgument('num'); $i++) {
            $channel->batch_basic_publish($msg, $this->exchange, $input->getArgument('queue'));

            if (($i % 50) == 0) {
                $channel->publish_batch();
            }

            $msg->setBody(
                "{
                    'id': " . $i . ",
                    'call_start': 'timestamp',
                    'call_end': 'timestamp',
                    'duration': " . rand(10, 150) . ",
                    'destination': " . rand(1, 256) .
                "}"
            );

        }

        // Publish the final batch of messages.
        $channel->publish_batch();

        $output->writeln("<info>Done");

        $channel->close();
        $connection->close();
    }
}
