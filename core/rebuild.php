<?php

/**
 * @file
 * Rebuilds all Drupal caches even when Drupal itself does not work.
 *
 * Needs a token query argument which can be calculated using the
 * scripts/rebuild_token_calculator.sh script.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Rebuilder;
use Drupal\Core\DrupalKernel;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Change the directory to the Drupal root.
chdir('..');

$autoloader = require_once __DIR__ . '/../autoload.php';

$request = Request::createFromGlobals();
// Manually resemble early bootstrap of DrupalKernel::boot().
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
  ($request->query->get('token') && $request->query->get('timestamp') &&
    ((REQUEST_TIME - $request->query->get('timestamp')) < 300) &&
    hash_equals(Crypt::hmacBase64($request->query->get('timestamp'), Settings::get('hash_salt')), $request->query->get('token'))
  )) {
  // Clear user cache for all major platforms.
  $user_caches = [
    'apcu_clear_cache',
    'wincache_ucache_clear',
  ];
  array_map('call_user_func', array_filter($user_caches, 'is_callable'));

  // Remove Drupal's error and exception handlers; they rely on a working
  // service container and other subsystems and will only cause a fatal error
  // that hides the actual error.
  restore_error_handler();
  restore_exception_handler();

  // Force kernel to rebuild php cache.
  PhpStorageFactory::get('twig')->deleteAll();

  // Bootstrap up to where caches exist and clear them.
  $kernel = new DrupalKernel('prod', $autoloader);
  $kernel->setSitePath(DrupalKernel::findSitePath($request));

  // Invalidate the container.
  $kernel->invalidateContainer();

  // Prepare a NULL request.
  // Reboot the kernel with new container.
  $kernel->boot();
  $kernel->preHandle($request);
  // Ensure our request includes the session if appropriate.
  if (PHP_SAPI !== 'cli') {
    $request->setSession($kernel->getContainer()->get('session'));
  }

  Rebuilder::deleteAllCacheBins();

  // Disable recording of cached pages.
  \Drupal::service('page_cache_kill_switch')->trigger();

  Rebuilder::rebuildAll();

  // Restore Drupal's error and exception handlers.
  // @see \Drupal\Core\DrupalKernel::boot()
  set_error_handler('_drupal_error_handler');
  set_exception_handler('_drupal_exception_handler');

  \Drupal::messenger()->addStatus('Cache rebuild complete.');
}
$base_path = dirname($request->getBaseUrl(), 2);
header('Location: ' . $request->getSchemeAndHttpHost() . $base_path);
