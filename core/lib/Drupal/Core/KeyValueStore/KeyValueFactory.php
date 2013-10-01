<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueFactory.
 */

namespace Drupal\Core\KeyValueStore;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the key/value store factory.
 */
class KeyValueFactory {

  /**
   * Instantiated stores, keyed by collection name.
   *
   * @var array
   */
  protected $stores = array();

  /**
   * var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;


  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   */
  function __construct(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * Constructs a new key/value store for a given collection name.
   *
   * @param string $collection
   *   The name of the collection holding key and value pairs.
   *
   * @return \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   *   A key/value store implementation for the given $collection.
   */
  public function get($collection) {
    global $conf;
    if (!isset($this->stores[$collection])) {
      if (isset($conf['keyvalue_service_' . $collection])) {
        $service_name = $conf['keyvalue_service_' . $collection];
      }
      elseif (isset($conf['keyvalue_default'])) {
        $service_name = $conf['keyvalue_default'];
      }
      else {
        $service_name = 'keyvalue.database';
      }
      $this->stores[$collection] = $this->container->get($service_name)->get($collection);
    }
    return $this->stores[$collection];
  }
}

