<?php

use Symfony\Component\Console\Input\ArgvInput;
use DrupalFinder\DrupalFinder;
use Drupal\Console\Core\Utils\ArgvInputReader;
use Drupal\Console\Bootstrap\Drupal;
use Drupal\Console\Application;

set_time_limit(0);

$autoloaders = [];

if (file_exists(__DIR__ . '/../autoload.local.php')) {
    include_once __DIR__ . '/../autoload.local.php';
} else {
    $autoloaders = [
        __DIR__ . '/../../../autoload.php',
        __DIR__ . '/../vendor/autoload.php'
    ];
}

foreach ($autoloaders as $file) {
    if (file_exists($file)) {
        $autoloader = $file;
        break;
    }
}

if (isset($autoloader)) {
    $autoload = include_once $autoloader;
} else {
    echo ' You must set up the project dependencies using `composer install`' . PHP_EOL;
    exit(1);
}

$argvInput = new ArgvInput();
$debug = $argvInput->hasParameterOption(['--debug']);

$drupalFinder = new DrupalFinder();
if (!$drupalFinder->locateRoot(getcwd())) {
    echo ' DrupalConsole must be executed within a Drupal Site.'.PHP_EOL;

    exit(1);
}

$composerRoot = $drupalFinder->getComposerRoot();
$drupalRoot = $drupalFinder->getDrupalRoot();
chdir($drupalRoot);

$drupal = new Drupal($autoload, $composerRoot, $drupalRoot);
$container = $drupal->boot($debug);

if (!$container) {
    echo ' Something was wrong. Drupal can not be bootstrap.';

    exit(1);
}

$configuration = $container->get('console.configuration_manager')
    ->getConfiguration();

$argvInputReader = new ArgvInputReader();
if ($options = $configuration->get('application.options') ?: []) {
    $argvInputReader->setOptionsFromConfiguration($options);
}
$argvInputReader->setOptionsAsArgv();
$application = new Application($container);
$application->setDefaultCommand('about');
$application->run();
