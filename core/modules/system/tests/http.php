<?php

/**
 * @file
 * Fake an HTTP request, for use during testing.
 */

declare(strict_types=1);

use Drupal\Core\Test\TestKernel;
use Symfony\Component\HttpFoundation\Request;

chdir('../../../..');

$autoloader = require_once 'autoload.php';

// Change to HTTP.
$_SERVER['HTTPS'] = NULL;
ini_set('session.cookie_secure', FALSE);
foreach ($_SERVER as &$value) {
  if (!is_string($value)) {
    continue;
  }
  // Because HTTPS is null.
  $value = $value ? str_replace('core/modules/system/tests/http.php', 'index.php', $value) : "";
  $value = $value ? str_replace('https://', 'http://', $value) : "";
}

$kernel = new TestKernel('testing', $autoloader, TRUE);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);
