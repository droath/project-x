<?php

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Droath\ConsoleForm\FormDiscovery;
use Droath\ConsoleForm\FormHelper;
use Droath\ProjectX\Discovery\PhpClassDiscovery;
use Droath\ProjectX\Discovery\ProjectXDiscovery;
use Droath\ProjectX\ProjectX;
use Robo\Config;
use Robo\Robo;
use Robo\Runner;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

define('APP_ROOT', dirname(__DIR__));

if (file_exists(APP_ROOT . '/../../vendor/autoload.php')) {
    $autoloader = include_once APP_ROOT . '/../../vendor/autoload.php';
} elseif (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    $autoloader = include_once APP_ROOT . '/vendor/autoload.php';
} elseif (file_exists(APP_ROOT . '/../../autoload.php')) {
    $autoloader = include_once APP_ROOT . '/../../autoload.php';
} else {
    echo 'Unable to find PHP autoloader' . PHP_EOL .
    exit(1);
}

$input = new ArgvInput($_SERVER['argv']);
$output = new ConsoleOutput();

$formDiscovery = (new FormDiscovery())
    ->discover(APP_ROOT . '/src/Form', '\Droath\ProjectX\Form');

$app = (new ProjectX())
    ->discoverCommands();
$app->getHelperSet()
    ->set(new FormHelper($formDiscovery));

$projectPath = (new ProjectXDiscovery())->execute();
ProjectX::setProjectPath($projectPath);

// Construct the default Robo container.
$container = Robo::createDefaultContainer($input, $output, $app, new Config());
ProjectX::setDefaultServices($container);

// Set the Robo container inside the project-x app.
$app->setContainer($container);

// Auto discover the Robo tasks command files if the project contains a
// project-x configuration.
$commandClasses = (new PhpClassDiscovery())
    ->loadClasses()
    ->setSearchPattern('*Tasks.php')
    ->addSearchLocations(ProjectX::taskLocations())
    ->discover();

$statusCode = (new Runner())
    ->setContainer($container)
    ->run(
        $input,
        $output,
        $app,
        $commandClasses
    );

exit($statusCode);
