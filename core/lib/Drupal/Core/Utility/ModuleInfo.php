<?php

/**
 * @file
 * Definition of Drupal\Core\Utility\ModuleInfo.
 */

namespace Drupal\Core\Utility;

use Drupal\Core\Utility\CacheArray;

/**
 * Extends CacheArray to lazy load .info.yml properties for modules.
 *
 * Use system_get_module_info() rather than instantiating this class directly.
 */
class ModuleInfo extends CacheArray {

  /**
   * The full module info array as returned by system_get_info().
   */
  protected $info;

  /**
   * Implements CacheArray::resolveCacheMiss().
   */
  function resolveCacheMiss($offset) {
    $data = array();
    if (!isset($this->info)) {
      $this->info = system_get_info('module');
    }
    foreach ($this->info as $module => $info) {
      if (isset($info[$offset])) {
        $data[$module] = $info[$offset];
      }
    }
    $this->storage[$offset] = $data;
    $this->persist($offset);
    return $data;
  }
}
