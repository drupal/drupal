#!/usr/bin/env php
<?php

/**
 * @file
 * A command line application to install Drupal for tests.
 */

use Drupal\TestSite\TestSiteApplication;

if (PHP_SAPI !== 'cli') {
  return;
}

// Use the PHPUnit bootstrap to prime an autoloader that works for test classes.
// Note we have to disable the SYMFONY_DEPRECATIONS_HELPER to ensure deprecation
// notices are not triggered.
putenv('SYMFONY_DEPRECATIONS_HELPER=disabled');
require_once __DIR__ . '/../tests/bootstrap.php';

// The application version is 0.1.0 to indicate that it is for internal use only
// and not currently API.
$app = new TestSiteApplication('test-site', '0.1.0');
$app->run();
