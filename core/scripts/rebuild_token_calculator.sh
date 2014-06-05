#!/usr/bin/env php
<?php

/**
 * @file
 * Command line token calculator for rebuild.php.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

// Check for $_SERVER['argv'] instead of PHP_SAPI === 'cli' to allow this script
// to be tested with the Simpletest UI test runner.
// @see \Drupal\system\Tests\System\ScriptTest
if (!isset($_SERVER['argv']) || !is_array($_SERVER['argv'])) {
  return;
}

$core = dirname(__DIR__);
require_once $core . '/vendor/autoload.php';
require_once $core . '/includes/bootstrap.inc';

drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

$timestamp = time();
$token = Crypt::hmacBase64($timestamp, Settings::get('hash_salt'));

print "timestamp=$timestamp&token=$token\n";
