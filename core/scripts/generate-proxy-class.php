#!/usr/bin/env php
<?php

/**
 * @file
 * A command line application to generate proxy classes.
 */

use Drupal\Core\Command\GenerateProxyClassApplication;
use Drupal\Core\DrupalKernel;
use Drupal\Core\ProxyBuilder\ProxyBuilder;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') {
  return;
}

// Bootstrap.
$autoloader = require __DIR__ . '/../../autoload.php';
$request = Request::createFromGlobals();
Settings::initialize(dirname(__DIR__, 2), DrupalKernel::findSitePath($request), $autoloader);
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'prod')->boot();

// Run the database dump command.
$application = new GenerateProxyClassApplication(new ProxyBuilder());
$application->run();
