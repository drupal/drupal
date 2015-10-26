<?php

/**
 * @file
 * Rebuilds all Drupal caches even when Drupal itself does not work.
 *
 * Needs a token query argument which can be calculated using the
 * scripts/rebuild_token_calculator.sh script.
 *
 * @see drupal_rebuild()
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Change the directory to the Drupal root.
chdir('..');

$autoloader = require_once __DIR__ . '/../autoload.php';
require_once __DIR__ . '/includes/utility.inc';

$request = Request::createFromGlobals();
// Manually resemble early bootstrap of DrupalKernel::boot().
require_once __DIR__ . '/includes/bootstrap.inc';
DrupalKernel::bootEnvironment();

try {
  Settings::initialize(dirname(__DIR__), DrupalKernel::findSitePath($request), $autoloader);
}
catch (HttpExceptionInterface $e) {
  $response = new Response('', $e->getStatusCode());
  $response->prepare($request)->send();
  exit;
}

if (Settings::get('rebuild_access', FALSE) ||
  ($request->get('token') && $request->get('timestamp') &&
    ((REQUEST_TIME - $request->get('timestamp')) < 300) &&
    Crypt::hashEquals(Crypt::hmacBase64($request->get('timestamp'), Settings::get('hash_salt')), $request->get('token'))
  )) {
  // Clear the APC cache to ensure APC class loader is reset.
  if (function_exists('apc_clear_cache')) {
    apc_clear_cache('user');
  }
  if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
  }
  drupal_rebuild($autoloader, $request);
  drupal_set_message('Cache rebuild complete.');
}
$base_path = dirname(dirname($request->getBaseUrl()));
header('Location: ' . $base_path);
