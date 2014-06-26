#!/usr/bin/env php
<?php

/**
 * @file
 * Command line token calculator for rebuild.php.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

// Check for $_SERVER['argv'] instead of PHP_SAPI === 'cli' to allow this script
// to be tested with the Simpletest UI test runner.
// @see \Drupal\system\Tests\System\ScriptTest
if (!isset($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
  return;
}

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/bootstrap.inc';

$request = Request::createFromGlobals();
Settings::initialize(DrupalKernel::findSitePath($request));

$timestamp = time();
$token = Crypt::hmacBase64($timestamp, Settings::get('hash_salt'));

print "timestamp=$timestamp&token=$token\n";
