#!/usr/bin/env php
<?php

/**
 * @file
 * A command line application to install drupal for tests.
 */

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Setup\Commands\TestInstallationSetupApplication;
use Symfony\Component\HttpFoundation\Request;

if (PHP_SAPI !== 'cli') {
  return;
}

// Bootstrap.
$autoloader = require __DIR__ . '/../../autoload.php';

$request = Request::createFromGlobals();
$kernel = DrupalKernel::createFromRequest($request, $autoloader, 'testing');
DrupalKernel::bootEnvironment($kernel->getAppRoot());

Settings::initialize(dirname(dirname(__DIR__)),
  DrupalKernel::findSitePath($request), $autoloader);

require_once __DIR__ . '/../tests/bootstrap.php';

(new TestInstallationSetupApplication())
  ->run();
