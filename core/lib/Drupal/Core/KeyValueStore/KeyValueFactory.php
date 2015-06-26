<?php

/**
 * @file
 * Contains \Drupal\Core\KeyValueStore\KeyValueFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the key/value store factory.
 */
class KeyValueFactory implements KeyValueFactoryInterface {

  /**
   * The specific setting name prefix.
   *
   * The collection name will be prefixed with this constant and used as a
   * setting name. The setting value will be the id of a service.
   */
  const SPECIFIC_PREFIX = 'keyvalue_service_';

  /**
   * The default setting name.
   *
   * This is a setting name that will be used if the specific setting does not
   * exist. The setting value will be the id of a service.
   */
  const DEFAULT_SETTING = 'default';

  /**
   * The default service id.
   *
   * If the default setting does not exist, this is the default service id.
   */
  const DEFAULT_SERVICE = 'keyvalue.database';

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
   *   The service container.
   * @param array $options
   *   (optional) Collection-specific storage override options.
   */
  function __construct(ContainerInterface $container, array $options = array()) {
    $this->container = $container;
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->stores[$collection])) {
      if (isset($this->options[$collection])) {
        $service_id = $this->options[$collection];
      }
      elseif (isset($this->options[static::DEFAULT_SETTING])) {
        $service_id = $this->options[static::DEFAULT_SETTING];
      }
      else {
        $service_id = static::DEFAULT_SERVICE;
      }
      $this->stores[$collection] = $this->container->get($service_id)->get($collection);
    }
    return $this->stores[$collection];
  }

}

