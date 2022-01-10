#!/usr/bin/env php
<?php

/**
 * @file
 * A command line application to import a database generation script.
 */

use Drupal\Core\Command\DbToolsApplication;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') {
  return;
}

// Bootstrap.
$autoloader = require __DIR__ . '/../../autoload.php';
$request = Request::createFromGlobals();
Settings::initialize(dirname(__DIR__, 2), DrupalKernel::findSitePath($request), $autoloader);
DrupalKernel::createFromRequest($request, $autoloader, 'prod')->boot();

// Run the database dump command.
$application = new DbToolsApplication();
$application->run();
