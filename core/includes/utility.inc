<?php

/**
 * @file
 * Miscellaneous functions.
 */

use Drupal\Component\Utility\Variable;
use Drupal\Component\PhpStorage\PhpStorageFactory;
use Drupal\Core\Cache\Cache;

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
 * @deprecated Use \Drupal\Component\Utility\Variable::export().
 */
function drupal_var_export($var, $prefix = '') {
  return Variable::export($var, $prefix);
}

/**
 * Rebuilds all caches even when Drupal itself does not work.
 *
 * Requires DRUPAL_BOOTSTRAP_CONFIGURATION.
 *
 * @see rebuild.php
 */
function drupal_rebuild() {
  // drupal_bootstrap(DRUPAL_BOOTSTRAP_KERNEL) will build a new kernel. This
  // comes before DRUPAL_BOOTSTRAP_PAGE_CACHE.
  PhpStorageFactory::get('service_container')->deleteAll();
  PhpStorageFactory::get('twig')->deleteAll();

  // Disable the page cache.
  drupal_page_is_cacheable(FALSE);

  // Bootstrap up to where caches exist and clear them.
  drupal_bootstrap(DRUPAL_BOOTSTRAP_PAGE_CACHE);
  foreach (Cache::getBins() as $bin) {
    $bin->deleteAll();
  }

  drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
  drupal_flush_all_caches();
}
