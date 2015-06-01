<?php

namespace ImBritish;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Command\Command;

class StopCommand extends Command
{
    private $queue;
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
            ->setDescription('Stop any consumers for the given queue.')
            ->addArgument('queue', InputArgument::REQUIRED, 'The name of the queue you want to stop.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->queue = $input->getArgument('queue');

        // When running with multiple pids we must stop them all
        $pids = $this->pidHelper->getPids($this->queue);

        if (!$this->pidHelper->isRunning($this->queue)) {
            $output->writeln("<comment>Process for " . $this->queue . " isn't running</comment>");
            return;
        }

        $output->writeln("<info>Stopping Consumer " . $this->queue);

        foreach ($pids as $pid) {
            posix_kill($pid, SIGQUIT);
        }

        // Pause, should be enough to stop the process.
        sleep(1);

        $retryCount = 0;

        while ($this->pidHelper->isRunning($this->queue)) {
            $output->writeln("<info>     Waiting for Consumer " . $this->queue);
            sleep(1);

            $retryCount++;

            if ($retryCount > 5) {
                $output->writeln("<error>Unable to stop queue " . $this->queue);
                exit(1);
            }
        }

        $output->writeln("<info>Consumer " . $this->queue . " stopped");
    }
}
