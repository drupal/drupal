<?php

/**
 * @file
 * Miscellaneous functions.
 */

use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rebuilds all caches even when Drupal itself does not work.
 *
 * @param $class_loader
 *   The class loader. Normally Composer's ClassLoader, as included by the
 *   front controller, but may also be decorated; e.g.,
 *   \Symfony\Component\ClassLoader\ApcClassLoader, \Symfony\Component\ClassLoader\WinCacheClassLoader, or \Symfony\Component\ClassLoader\XcacheClassLoader
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The current request.
 *
 * @see rebuild.php
 */
function drupal_rebuild($class_loader, Request $request) {
  // Remove Drupal's error and exception handlers; they rely on a working
  // service container and other subsystems and will only cause a fatal error
  // that hides the actual error.
  restore_error_handler();
  restore_exception_handler();

  // Force kernel to rebuild php cache.
  PhpStorageFactory::get('twig')->deleteAll();

  // Bootstrap up to where caches exist and clear them.
  $kernel = new DrupalKernel('prod', $class_loader);
  $kernel->setSitePath(DrupalKernel::findSitePath($request));

  // Invalidate the container.
  $kernel->invalidateContainer();

  // Prepare a NULL request.
  $kernel->prepareLegacyRequest($request);

  foreach (Cache::getBins() as $bin) {
    $bin->deleteAll();
  }

  // Disable recording of cached pages.
  \Drupal::service('page_cache_kill_switch')->trigger();

  drupal_flush_all_caches();

  // Restore Drupal's error and exception handlers.
  // @see \Drupal\Core\DrupalKernel::boot()
  set_error_handler('_drupal_error_handler');
  set_exception_handler('_drupal_exception_handler');
}
