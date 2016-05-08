<?php

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the key/value store factory for the memory backend.
 */
class KeyValueMemoryFactory implements KeyValueFactoryInterface {

  /**
   * An array of keyvalue collections that are stored in memory.
   *
   * @var array
   */
  protected $collections = array();

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->collections[$collection])) {
      $this->collections[$collection] = new MemoryStorage($collection);
    }
    return $this->collections[$collection];
  }

}
