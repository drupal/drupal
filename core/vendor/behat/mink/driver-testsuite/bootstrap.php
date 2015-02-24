<?php

$file = __DIR__.'/../../../autoload.php';

if (!file_exists($file)) {
    echo PHP_EOL.'The Mink driver testsuite expects Mink to be installed as a composer dependency of your project'.PHP_EOL;
    exit(1);
}

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require $file;

$loader->addPsr4('Behat\Mink\Tests\Driver\\', __DIR__.'/tests');

// Clean the global variables
unset($file);
unset($loader);

// Check the definition of the driverLoaderFactory

if (!isset($GLOBALS['driver_config_factory'])) {
    echo PHP_EOL.'The "driver_config_factory" global variable must be set.'.PHP_EOL;
    exit(1);
}
if (!is_callable($GLOBALS['driver_config_factory'])) {
    echo PHP_EOL.'The "driver_config_factory" global variable must be a callable.'.PHP_EOL;
    exit(1);
}
