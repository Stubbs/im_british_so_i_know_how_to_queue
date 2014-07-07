<?php

namespace ImBritish;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument as InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as OutputInterface;

use PhpAmqpLib\Connection\AMQPConnection;

class ConsumeCommand extends Command
{
    private $queue = "chapter4";
    private $exchange = "im_british";

    protected function configure()
    {
        $this->setName('consume')
             ->setDescription('Consume messages from the "chapter4" queue.')
             ->setHelp('Consume');
         return;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = new AMQPConnection('localhost', 5672, 'client', 'client', '/');
        $channel = $connection->channel();
        $channel->queue_declare($this->queue, false, true, false, false);
        $channel->exchange_declare($this->exchange, 'topic', false, true, false);
        $channel->queue_bind($this->queue, $this->exchange);

        $channel->basic_consume(
            $this->queue,
            $this->queue . '_consumer',
            false,
            true,
            false,
            false,
            function ($msg) {
                echo $msg->body . "\n";
            }
        );

        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }
}
