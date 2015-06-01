<?php

namespace ImBritish;

/**
 * PidHelper class.
 */
class PidHelper
{
    private $path="/tmp";

    protected function writePidfile($queueName, $pids)
    {
        $handle = fopen($this->getFilename($queueName), "w");

        foreach ($pids as $pid) {
            fwrite($handle, trim($pid) . "\n");
        }

        fclose($handle);
    }

    public function savePidfile($queueName)
    {
        $currentPids = $this->getPids($queueName);

        $currentPids[] = getmypid();
        $this->writePidfile($queueName, $currentPids);
    }

    public function getPids($queueName)
    {
        if (!file_exists($this->getFilename($queueName))) {
            return array();
        }

        $pids = file($this->getFilename($queueName));

        return array_map('trim', $pids);
    }

    public function removePid($queueName, $pid)
    {
        if (file_exists($this->getFilename($queueName))) {
            $pids = $this->getPids($queueName);

            $newPids = array_diff($pids, array($pid));

            if (count($newPids) == 0) {
                $this->removePidfile($queueName);
                return;
            }

            $this->writePidfile($queueName, $newPids);
            return;
        }

        throw new \Exception('Something has gone very wrong, trying to remove pid $pid from the pidfile, but it has been deleted.');
    }

    public function removePidfile($queueName)
    {
        return @unlink($this->path . "/" . $queueName . "_consumer.pid");
    }

    public function isRunning($queueName)
    {
        if ($this->getProcessCount($queueName) <= 0) {
            return false;
        }

        $pids = $this->getPids($queueName);

        foreach ($pids as $pid) {
            exec("ps -p " . $pid, $output);

            if (count($output) < 1) {
                return false;
            }
        }

        return true;
    }

    public function getProcessCount($queueName)
    {
        return file_exists($this->getFilename($queueName)) ? count(file($this->getFilename($queueName))) : 0;
    }

    public function getFilename($queueName)
    {
        return $this->path . "/" . $queueName . "_consumer.pid";
    }
}
