<?php

namespace ImBritish;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument as InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as OutputInterface;

use PhpAmqpLib\Connection\AMQPConnection;

use ImBritish\PidHelper;

class ConsumeCommand extends Command
{
    private $queue = "chapter5";
    private $exchange = "im_british";
    private $continue = true;

    private $output;

    public function __construct(PidHelper $pidHelper)
    {
        parent::__construct();

        $this->pidHelper = $pidHelper;
    }

    protected function configure()
    {
        $this->setName('consume')
             ->setDescription('Consume messages from the ' . $this->queue . ' queue.')
             ->setHelp('Consume');
         return;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        declare(ticks=15);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);

        $this->pidHelper->savePidfile($this->queue);

        // Share the output stream with the rest of the class.
        $this->output = $output;

        $this->output->writeln('<info>Starting consumer ...');

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

        while ( $this->continue && count($channel->callbacks)) {
            try {
                // The @ symbol is here to stop an "error" from the 3rd party library barfing
                // all over out output.
                @$channel->wait(null, false, 1);
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // Do nothing.
            }
        }

        $channel->close();
        $connection->close();
    }

    public function handleSignal($signal)
    {
        $this->output->writeln('<info>Caught signal, stopping consumer ...');
        $this->continue = false;
    }
}
