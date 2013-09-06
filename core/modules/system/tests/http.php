<?php

/**
 * @file
 * Fake an HTTP request, for use during testing.
 */

// Set a global variable to indicate a mock HTTP request.
$is_http_mock = !empty($_SERVER['HTTPS']);

// Change to HTTP.
$_SERVER['HTTPS'] = NULL;
ini_set('session.cookie_secure', FALSE);
foreach ($_SERVER as $key => $value) {
  $_SERVER[$key] = str_replace('core/modules/system/tests/http.php', 'index.php', $value);
  $_SERVER[$key] = str_replace('https://', 'http://', $_SERVER[$key]);
}

// Change current directory to the Drupal root.
chdir('../../../..');
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once dirname(dirname(dirname(__DIR__))) . '/includes/bootstrap.inc';
drupal_handle_request(TRUE);
