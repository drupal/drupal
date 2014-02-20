#!/usr/bin/env php

<?php

/**
 * @file
 * Command line token calculator for rebuild.php.
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/bootstrap.inc';

use Drupal\Component\Utility\Crypt;

drupal_bootstrap(DRUPAL_BOOTSTRAP_CONFIGURATION);

if (!drupal_is_cli()) {
  exit;
}

$timestamp = time();
$token = Crypt::hmacBase64($timestamp, settings()->get('hash_salt'));

print "timestamp=$timestamp&token=$token\n";
