<?php

namespace ImBritish;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument as InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Channel\AMQPChannel;

use ImBritish\PidHelper;

/**
* @SuppressWarnings(StaticAccess)
*/
class ConsumeCommand extends Command
{
    private $exchange = "im_british";
    private $queue;
    private $continue = true;
    private $container;
    private $count = 0;

    private $output;

    public function __construct(PidHelper $pidHelper, ContainerBuilder $container)
    {
        parent::__construct();

        $this->pidHelper = $pidHelper;
        $this->container = $container;
    }

    protected function configure()
    {
        $this->setName('consume')
             ->setDescription('Consume messages from the ' . $this->queue . ' queue.')
             ->addArgument('queue', InputArgument::REQUIRED, 'The name of the queue you want to consume.')
             ->setHelp('Consume');
         return;
    }

    protected function checkConsumerStatus(AMQPChannel $channel)
    {
        // When you pasiveley declare a queue, you get back some basic info about it, it's name, the number of
        // messages & the number of attached consumers.
        $info = $channel->queue_declare($this->queue, true, true, false, false);

        // Check the ratio of consumers to queues & stop this one if the ratio is too low and it's not the last consumer.
        // New queues are spawned from CRON when the ratio is too high.
        if ($this->container->getParameter('queues')[$this->queue]['messages_per_consumer'] > 0
                && $info[2] > 0
                && ($info[1] / $info[2]) < $this->container->getParameter('queues')[$this->queue]['messages_per_consumer']
                && $this->pidHelper->getProcessCount($this->queue) > 1) {

            $this->output->writeln('<comment>The ratio of consumers (' . $info[1] / $info[2] . ') to messages is not high enough, this consumer will stop');
            $this->continue = false;
        }

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Share the output stream with the rest of the class.
        $this->output = $output;
        $this->queue = $input->getArgument('queue');

        // Look at how many PIDs are in the pid file.
        $pidCount = $this->pidHelper->getProcessCount($this->queue);

        if ($pidCount >= $this->container->getParameter('queues')[$this->queue]['max_consumers'] && $this->pidHelper->isRunning($this->queue)) {
            $this->output->writeln('<error>Too many consumers, max ' . $this->container->getParameter('queues')[$this->queue]['max_consumers'] . ' ...');
            exit(2);
        }

        if (!$this->pidHelper->isRunning($this->queue) && $pidCount > 0) {
            throw new \Exception("Pidfile contains more IDs than running processes, that's not right.");
        }

        $output->writeln("<info>Starting consumer #" . ($pidCount + 1) . " of max " . $this->container->getParameter('queues')[$this->queue]['max_consumers']);

        declare(ticks=15);
        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGQUIT, [$this, 'handleSignal']);

        $this->pidHelper->savePidfile($this->queue);

        $this->output->writeln('<info>Starting consumer ...');

        $connection = new AMQPConnection('localhost', 5672, 'client', 'client', '/');
        $channel = $connection->channel();
        $channel->queue_declare($this->queue, false, true, false, false);

        $this->checkConsumerStatus($channel);

        $channel->exchange_declare($this->exchange, 'direct', false, true, false);

        // We'll use the queue name as the routing key for this example,
        // but it can be what you like.
        $channel->queue_bind($this->queue, $this->exchange, $input->getArgument('queue'));
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(
            $this->queue,
            $this->queue . '_consumer',
            false,
            false,
            false,
            false,
            function ($msg) {
                echo $msg->body . "\n";

                // Let's pretend whatever we're doing with this message takes a while
                sleep(1);

                $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
            }
        );

        while ($this->continue && count($channel->callbacks)) {
            try {
                // The @ symbol is here to stop an "error" from the 3rd party library barfing
                // all over our output.
                @$channel->wait(null, false, 1);

                $this->count++;

                if ($this->count % 50 === 0) {
                    $this->checkConsumerStatus($channel);
                }
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                // Do nothing.
            } catch (\PhpAmqpLib\Exception\AMQPRuntimeException $e) {
                // Do nothing.
            }
        }

        $channel->close();
        $connection->close();

        $this->pidHelper->removePid($this->queue, getmypid());
    }

    public function handleSignal($signal)
    {
        $this->output->writeln('<info>Caught signal, stopping consumer ...');
        $this->continue = false;
    }
}
