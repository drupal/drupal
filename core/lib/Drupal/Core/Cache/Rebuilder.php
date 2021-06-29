<?php

namespace Drupal\Core\Cache;

/**
 * Helper methods for cache rebuild.
 *
 * @ingroup cache
 */
class Rebuilder {

  /**
   * Flushes all caches.
   *
   * Rebuilds the container, flushes all persistent caches, resets all
   * variables, and rebuilds all data structures.
   * At times, it is necessary to re-initialize the entire system to account for
   * changed or new code. This function:
   * - Rebuilds the container if $kernel is not passed in.
   * - Clears all persistent caches:
   *   - The bootstrap cache bin containing base system, module system, and
   *     theme system information.
   *   - The common 'default' cache bin containing arbitrary caches.
   *   - The page cache.
   *   - The URL alias path cache.
   * - Resets all static variables that have been defined via drupal_static().
   * - Clears asset (JS/CSS) file caches.
   * - Updates the system with latest information about extensions (modules and
   *   themes).
   * - Updates the bootstrap flag for modules implementing bootstrap_hooks().
   * - Rebuilds the full database schema information (invoking hook_schema()).
   * - Rebuilds data structures of all modules (invoking hook_rebuild()). In
   *   core this means
   *   - blocks, node types, date formats and actions are synchronized with the
   *     database
   *   - The 'active' status of fields is refreshed.
   * - Rebuilds the menu router.
   *
   * It's discouraged to call this during a regular page request.
   * If you call this function in tests, every code afterwards should use the
   * new container.
   *
   * This means the entire system is reset so all caches and static variables
   * are effectively empty. After that is guaranteed, information about the
   * currently active code is updated, and rebuild operations are successively
   * called in order to synchronize the active system according to the current
   * information defined in code.
   *
   * All modules need to ensure that all of their caches are flushed when
   * hook_cache_flush() is invoked; any previously known information must no
   * longer exist. All following hook_rebuild() operations must be based on
   * fresh and current system data. All modules must be able to rely on this
   * contract.
   *
   * @see \Drupal\Core\Cache\CacheHelper::getBins()
   * @see hook_cache_flush()
   * @see hook_rebuild()
   *
   * This function also resets the theme, which means it is not initialized
   * anymore and all previously added JavaScript and CSS is gone. Normally, this
   * function is called as an end-of-POST-request operation that is followed by
   * a redirect, so this effect is not visible. Since the full reset is the
   * whole point of this function, callers need to take care for backing up all
   * needed variables and properly restoring or re-initializing them on their
   * own. For convenience, this function automatically re-initializes the
   * maintenance theme if it was initialized before.
   *
   * @todo Try to clear page/JS/CSS caches last, so cached pages can still be
   *   served during this possibly long-running operation. (Conflict on bootstrap
   *   cache though.)
   * @todo Add a global lock to ensure that caches are not primed in concurrent
   *   requests.
   *
   * @param \Drupal\Core\DrupalKernel|array $kernel
   *   (optional) The Drupal Kernel. It is the caller's responsibility to rebuild
   *   the container if this is passed in. Sometimes drupal_flush_all_caches is
   *   used as a batch operation so $kernel will be an array, in this instance it
   *   will be treated as if it it NULL.
   *
   * @see \Drupal\Core\Cache\CacheHelper::getBins()
   * @see hook_cache_flush()
   * @see hook_rebuild()
   */
  public static function rebuildAll($kernel): void {
    // This is executed based on old/previously known information if $kernel is
    // not passed in, which is sufficient, since new extensions cannot have any
    // primed caches yet.
    $module_handler = \Drupal::moduleHandler();
    // Flush all persistent caches.
    $module_handler->invokeAll('cache_flush');
    foreach (Cache::getBins() as $cache_backend) {
      $cache_backend->deleteAll();
    }

    // Flush asset file caches.
    \Drupal::service('asset.css.collection_optimizer')->deleteAll();
    \Drupal::service('asset.js.collection_optimizer')->deleteAll();
    \Drupal::service('cache.query_string')->reset();

    // Reset all static caches.
    drupal_static_reset();

    // Wipe the Twig PHP Storage cache.
    \Drupal::service('twig')->invalidate();

    // Rebuild theme data that is stored in state.
    \Drupal::service('theme_handler')->refreshInfo();
    // In case the active theme gets requested later in the same request we need
    // to reset the theme manager.
    \Drupal::theme()->resetActiveTheme();

    if (!$kernel instanceof DrupalKernel) {
      $kernel = \Drupal::service('kernel');
      $kernel->invalidateContainer();
      $kernel->rebuildContainer();
    }

    // Rebuild module data that is stored in state.
    \Drupal::service('extension.list.module')->reset();

    // Rebuild all information based on new module data.
    \Drupal::moduleHandler()->invokeAll('rebuild');

    // Clear all plugin caches.
    \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

    // Rebuild the menu router based on all rebuilt data.
    // Important: This rebuild must happen last, so the menu router is guaranteed
    // to be based on up to date information.
    \Drupal::service('router.builder')->rebuild();

    // Re-initialize the maintenance theme, if the current request attempted to
    // use it. Unlike regular usages of this function, the installer and update
    // scripts need to flush all caches during GET requests/page building.
    if (function_exists('_drupal_maintenance_theme')) {
      \Drupal::theme()->resetActiveTheme();
      drupal_maintenance_theme();
    }
  }

  /**
   * Collects all bins and deletes all cache items in the each bin.
   */
  public static function deleteAllCacheBins(): void {
    foreach (Cache::getBins() as $bin) {
      $bin->deleteAll();
    }
  }

}
