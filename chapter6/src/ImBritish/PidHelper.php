<?php

namespace ImBritish;

/**
 * PidHelper class.
 */
class PidHelper
{
    private $path="/tmp";

    public function savePidfile($queueName)
    {
        return @file_put_contents($this->path . "/" . $queueName . "_consumer.pid", getmypid());
    }

    public function getPid($queueName)
    {
        return @file_get_contents($this->path . "/" . $queueName . "_consumer.pid");
    }

    public function removePidfile($queueName)
    {
        return @unlink($this->path . "/" . $queueName . "_consumer.pid");
    }

    public function isRunning($queueName)
    {
        if ($this->getPid($queueName) <= 0) {
            return false;
        }

        exec("ps -p " . $this->getPid($queueName), $output);
        if (count($output) > 1) {
            return true;
        }

        return false;

    }
}
