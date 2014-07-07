<?php

require_once __DIR__.'/vendor/autoload.php';

use ImBritish\ConsumeCommand;
use ImBritish\QueueCommand;
use Symfony\Component\Console\Application;

$app = new Application('ImBritish', '0.0.1');

$app->addCommands([new ConsumeCommand()]);

$app->run();
