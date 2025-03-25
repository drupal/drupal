<?php

/**
 * @file
 * Miscellaneous functions.
 */

use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rebuilds all caches even when Drupal itself does not work.
 *
 * @param \Composer\Autoload\ClassLoader $class_loader
 *   The class loader. Normally Composer's ClassLoader, as included by the
 *   front controller, but may also be decorated.
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The current request.
 *
 * @see rebuild.php
 */
function drupal_rebuild($class_loader, Request $request): void {
  // Remove Drupal's error and exception handlers; they rely on a working
  // service container and other subsystems and will only cause a fatal error
  // that hides the actual error.
  restore_error_handler();
  restore_exception_handler();

  // Invalidate the container.
  // Bootstrap up to where caches exist and clear them.
  $kernel = new DrupalKernel('prod', $class_loader);
  $kernel->setSitePath(DrupalKernel::findSitePath($request));
  $kernel->invalidateContainer();
  $kernel->boot();
  $kernel->preHandle($request);
  // Ensure our request includes the session if appropriate.
  if (PHP_SAPI !== 'cli') {
    $request->setSession($kernel->getContainer()->get('session'));
  }

  drupal_flush_all_caches($kernel);

  // Disable recording of cached pages.
  \Drupal::service('page_cache_kill_switch')->trigger();

  // Restore Drupal's error and exception handlers.
  // @see \Drupal\Core\DrupalKernel::boot()
  set_error_handler('_drupal_error_handler');
  set_exception_handler('_drupal_exception_handler');
}
