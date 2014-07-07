<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

use ImBritish\ConsumeCommand;
use ImBritish\StopCommand;

$container = new ContainerBuilder();

// Load service definitions.
$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/config/'));
$loader->load('services.yml');

// Our App object.
$app = $container->get('symfony.application');

// Add a couple of services as defined in the config, not the constructor
// parameters are also passed in that config.
$app->addCommands([
    $container->get('command.consume'),
    $container->get('command.stop'),
    ]);

$app->run();
