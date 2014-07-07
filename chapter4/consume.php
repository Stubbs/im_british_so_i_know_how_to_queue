<?php

require_once __DIR__.'/vendor/autoload.php';

use ImBritish\ConsumeCommand;
use ImBritish\QueueCommand;
use Symfony\Component\Console\Application;

$app = new Application('ImBritish', '0.0.1');

# Uncomment this line for Chapter 4 part 1
#$app->addCommands([new ConsumeCommand()]);

# Uncomment this line for Chapter 4 part 2, specify the queue on the command line.
$app->addCommands([new ConsumeCommand(), new QueueCommand]);

$app->run();
