<?php

/**
 * @file
 * Fake an HTTP request, for use during testing.
 */

use Drupal\Core\Test\TestKernel;
use Symfony\Component\HttpFoundation\Request;

chdir('../../../..');

$autoloader = require_once 'autoload.php';

// Set a global variable to indicate a mock HTTP request.
$is_http_mock = !empty($_SERVER['HTTPS']);

// Change to HTTP.
$_SERVER['HTTPS'] = NULL;
ini_set('session.cookie_secure', FALSE);
foreach ($_SERVER as &$value) {
  $value = str_replace('core/modules/system/tests/http.php', 'index.php', $value);
  $value = str_replace('https://', 'http://', $value);
}

$request = Request::createFromGlobals();
$kernel = TestKernel::createFromRequest($request, $autoloader, 'testing', TRUE);
$response = $kernel
  ->handle($request)
    // Handle the response object.
    ->prepare($request)->send();
$kernel->terminate($request, $response);
