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

if (PHP_SAPI !== 'cli') {
  return;
}

$autoloader = require __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../includes/bootstrap.inc';

$request = Request::createFromGlobals();
Settings::initialize(DRUPAL_ROOT, DrupalKernel::findSitePath($request), $autoloader);

$timestamp = time();
$token = Crypt::hmacBase64($timestamp, Settings::get('hash_salt'));

print "timestamp=$timestamp&token=$token\n";
