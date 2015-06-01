<?php

namespace ImBritish;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument as InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as OutputInterface;

use PhpAmqpLib\Connection\AMQPConnection;

class QueueCommand extends Command
{
    protected function configure()
    {
        $this->setName('queue')
             ->setDescription('Consume messages from the "chapter4" queue.')
             ->setHelp('Consume')
             ->addArgument('queue', InputArgument::REQUIRED, 'The name of the queue you want to consume.', 'chapter4');
         return;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $queue = $input->getArgument('queue');
        $exchange = 'im_british_examples';

        $connection = new AMQPConnection('localhost', 5672, 'client', 'client', '/');
        $channel = $connection->channel();
        $channel->queue_declare($queue, false, true, false, false);
        $channel->exchange_declare($exchange, 'direct', false, true, false);
        $channel->queue_bind($queue, $exchange);

        $channel->basic_consume($queue, 'chapter4_consumer', false, true, false, false,
            function ($msg) {
                echo $msg->body . "\n";
            }
        );

        while(count($channel->callbacks)) {
            $channel->wait();
        }
    }
}
