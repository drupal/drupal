<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueExpirableFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the key/value store factory.
 */
class KeyValueExpirableFactory extends KeyValueFactory {

  /**
   * Constructs a new expirable key/value store for a given collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   *   An expirable key/value store implementation for the given $collection.
   */
  public function get($collection) {
    global $conf;
    if (!isset($this->stores[$collection])) {
      if (isset($conf['keyvalue_expirable_service_' . $collection])) {
        $service_name = $conf['keyvalue_expirable_service_' . $collection];
      }
      elseif (isset($conf['keyvalue_expirable_default'])) {
        $service_name = $conf['keyvalue_expirable_default'];
      }
      else {
        $service_name = 'keyvalue.expirable.database';
      }
      $this->stores[$collection] = $this->container->get($service_name)->get($collection);
    }
    return $this->stores[$collection];
  }
}

