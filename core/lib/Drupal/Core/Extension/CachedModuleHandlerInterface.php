<?php

/**
 * @file
 * Contains Drupal\Core\Extension\CachedModuleHandlerInterface.
 */

namespace Drupal\Core\Extension;

/**
 * Interface for cacheable module handlers.
 */
interface CachedModuleHandlerInterface extends ModuleHandlerInterface {

  /**
   * Write the hook implementation info to the cache.
   */
  public function writeCache();

}
