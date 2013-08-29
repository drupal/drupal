<?php

/**
 * @file
 * Definition of SchemaCache
 */

namespace Drupal\Core\Utility;

use Drupal\Core\Utility\CacheArray;

/**
 * Extends CacheArray to allow for dynamic building of the schema cache.
 */
class SchemaCache extends CacheArray {

  /**
   * Constructs a SchemaCache object.
   */
  public function __construct() {
    $request = \Drupal::request();
    // Cache by request method.
    parent::__construct('schema:runtime:' . ($request->isMethod('GET')), 'cache', array('schema' => TRUE));
  }

  /**
   * Implements CacheArray::resolveCacheMiss().
   */
  protected function resolveCacheMiss($offset) {
    $complete_schema = drupal_get_complete_schema();
    $value = isset($complete_schema[$offset]) ? $complete_schema[$offset] :  NULL;
    $this->storage[$offset] = $value;
    $this->persist($offset);
    return $value;
  }
}
