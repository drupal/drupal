<?php

/**
 * @file
 * Fake an HTTPS request, for use during testing.
 *
 * @todo Fix this to use a new request rather than modifying server variables,
 *   see http.php.
 */

use Drupal\Core\Test\TestKernel;
use Symfony\Component\HttpFoundation\Request;

chdir('../../../..');

$autoloader = require_once 'autoload.php';

// Set a global variable to indicate a mock HTTPS request.
$is_https_mock = empty($_SERVER['HTTPS']);

// Change to HTTPS.
$_SERVER['HTTPS'] = 'on';
foreach ($_SERVER as &$value) {
  $value = str_replace('core/modules/system/tests/https.php', 'index.php', $value);
  $value = str_replace('http://', 'https://', $value);
}

$request = Request::createFromGlobals();
$kernel = TestKernel::createFromRequest($request, $autoloader, 'testing', TRUE);
$response = $kernel
  ->handle($request)
    // Handle the response object.
    ->prepare($request)->send();
$kernel->terminate($request, $response);
