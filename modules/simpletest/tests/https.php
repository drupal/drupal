<?php
// $Id: https.php,v 1.2 2009/11/04 05:05:52 webchick Exp $

/**
 * @file
 * Fake an https request, for use during testing.
 */

// Negated copy of the condition in _drupal_bootstrap(). If the user agent is
// not from simpletest then disallow access.
if (!(isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], "simpletest") !== FALSE))) {
  exit;
}

// Set a global variable to indicate a mock HTTPS request.
$is_https_mock = empty($_SERVER['HTTPS']);

// Change to https.
$_SERVER['HTTPS'] = 'on';

// Change to index.php.
chdir('../../..');
foreach ($_SERVER as $key => $value) {
  $_SERVER[$key] = str_replace('modules/simpletest/tests/https.php', 'index.php', $value);
  $_SERVER[$key] = str_replace('http://', 'https://', $_SERVER[$key]);
}

require_once 'index.php';
