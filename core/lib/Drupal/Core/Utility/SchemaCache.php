<?php

/**
 * @file
 * Definition of SchemaCache
 */

namespace Drupal\Core\Utility;

use Drupal\Core\Utility\CacheArray;

/**
 * Extends DrupalCacheArray to allow for dynamic building of the schema cache.
 */
class SchemaCache extends CacheArray {

  /**
   * Constructs a SchemaCache object.
   */
  public function __construct() {
    // Cache by request method.
    parent::__construct('schema:runtime:' . ($_SERVER['REQUEST_METHOD'] == 'GET'), 'cache');
  }

  /**
   * Overrides DrupalCacheArray::resolveCacheMiss().
   */
  protected function resolveCacheMiss($offset) {
    $complete_schema = drupal_get_complete_schema();
    $value = isset($complete_schema[$offset]) ? $complete_schema[$offset] :  NULL;
    $this->storage[$offset] = $value;
    $this->persist($offset);
    return $value;
  }
}
