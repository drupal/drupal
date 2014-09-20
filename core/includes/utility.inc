<?php

/**
 * @file
 * Miscellaneous functions.
 */

use Drupal\Component\Utility\Variable;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DrupalKernel;
use Symfony\Component\HttpFoundation\Request;
use Composer\Autoload\ClassLoader;

/**
 * Drupal-friendly var_export().
 *
 * @param mixed $var
 *   The variable to export.
 * @param string $prefix
 *   A prefix that will be added at the beginning of every lines of the output.
 *
 * @return string
 *   The variable exported in a way compatible to Drupal's coding standards.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\Variable::export().
 */
function drupal_var_export($var, $prefix = '') {
  return Variable::export($var, $prefix);
}

/**
 * Rebuilds all caches even when Drupal itself does not work.
 *
 * Requires DRUPAL_BOOTSTRAP_CONFIGURATION.
 *
 * @param \Composer\Autoload\ClassLoader $classloader
 *   The classloader.
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The current request.
 *
 * @see rebuild.php
 */
function drupal_rebuild(ClassLoader $classloader, Request $request) {
  // Remove Drupal's error and exception handlers; they rely on a working
  // service container and other subsystems and will only cause a fatal error
  // that hides the actual error.
  restore_error_handler();
  restore_exception_handler();

  // Force kernel to rebuild container.
  PhpStorageFactory::get('service_container')->deleteAll();
  PhpStorageFactory::get('twig')->deleteAll();

  // Bootstrap up to where caches exist and clear them.
  $kernel = new DrupalKernel('prod', $classloader);
  $kernel->setSitePath(DrupalKernel::findSitePath($request));
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
