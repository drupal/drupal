<?php

/**
 * @file
 * Contains Drupal\Core\KeyValueStore\KeyValueFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Drupal\Core\Site\Settings;
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
  const DEFAULT_SETTING = 'keyvalue_default';

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
   * The read-only settings container.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\Core\Site\Settings $settings
   *  The read-only settings container.
   */
  function __construct(ContainerInterface $container, Settings $settings) {
    $this->container = $container;
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function get($collection) {
    if (!isset($this->stores[$collection])) {
      if ($service_name = $this->settings->get(static::SPECIFIC_PREFIX . $collection)) {
      }
      elseif ($service_name = $this->settings->get(static::DEFAULT_SETTING)) {
      }
      else {
        $service_name = static::DEFAULT_SERVICE;
      }
      $this->stores[$collection] = $this->container->get($service_name)->get($collection);
    }
    return $this->stores[$collection];
  }

}

