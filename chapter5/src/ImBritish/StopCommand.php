<?php

namespace ImBritish;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class StopCommand extends Command
{
    private $queue = "chapter5";
    private $container;
    private $pidHelper;

    public function __construct(PidHelper $pidHelper)
    {
        parent::__construct();

        $this->pidHelper = $pidHelper;
    }

    protected function configure()
    {
        $this
            ->setName('stop')
            ->setDescription('Stop any consumers for the given queue.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pid = $this->pidHelper->getPid($this->queue);

        if ($pid == -1) {
            $output->writeln("<comment>Process for " . $this->queue . " isn't running</comment>");
            return;
        }

        $output->writeln("<info>Stopping Consumer " . $this->queue . " (PID: " . $pid . ")");

        posix_kill($pid, SIGQUIT);

        // Pause, should be enough to stop the process.
        sleep(1);

        $retryCount = 0;

        while ($this->pidHelper->isRunning($this->queue)) {
            $output->writeln("<info>     Waiting for Consumer " . $this->queue . " (PID: " . $pid . ")");
            sleep(1);

            $retryCount++;

            if ($retryCount > 5) {
                $output->writeln("<error>Unable to stop queue " . $this->queue . " (PID: " . $pid . ")");
                exit(1);
            }
        }

        $output->writeln("<info>Consumer " . $this->queue . " stopped");
    }
}
