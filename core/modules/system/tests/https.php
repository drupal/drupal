<?php

/**
 * @file
 * Fake an HTTPS request, for use during testing.
 *
 * @todo Fix this to use a new request rather than modifying server variables,
 *   see http.php.
 */

// Set a global variable to indicate a mock HTTPS request.
$is_https_mock = empty($_SERVER['HTTPS']);

// Change to HTTPS.
$_SERVER['HTTPS'] = 'on';
foreach ($_SERVER as $key => $value) {
  $_SERVER[$key] = str_replace('core/modules/system/tests/https.php', 'index.php', $value);
  $_SERVER[$key] = str_replace('http://', 'https://', $_SERVER[$key]);
}

// Change current directory to the Drupal root.
chdir('../../../..');
require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once dirname(dirname(dirname(__DIR__))) . '/includes/bootstrap.inc';
drupal_handle_request(TRUE);
