<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\PluginBase
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginBase as ComponentPluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins supporting metadata inspection and translation.
 */
abstract class PluginBase extends ComponentPluginBase {
  use StringTranslationTrait;

  /**
   * An array of service IDs keyed by property name used for serialization.
   *
   * @todo Remove when Drupal\Core\DependencyInjection\DependencySerialization
   * is converted to a trait.
   *
   * @var array
   */
  protected $_serviceIds = array();

  /**
   * {@inheritdoc}
   *
   * @todo Remove when Drupal\Core\DependencyInjection\DependencySerialization
   * is converted to a trait.
   */
  public function __sleep() {
    $this->_serviceIds = array();
    $vars = get_object_vars($this);
    foreach ($vars as $key => $value) {
      if (is_object($value) && isset($value->_serviceId)) {
        // If a class member was instantiated by the dependency injection
        // container, only store its ID so it can be used to get a fresh object
        // on unserialization.
        $this->_serviceIds[$key] = $value->_serviceId;
        unset($vars[$key]);
      }
      // Special case the container, which might not have a service ID.
      elseif ($value instanceof ContainerInterface) {
        $this->_serviceIds[$key] = 'service_container';
        unset($vars[$key]);
      }
    }

    return array_keys($vars);
  }

  /**
   * {@inheritdoc}
   *
   * @todo Remove when Drupal\Core\DependencyInjection\DependencySerialization
   * is converted to a trait.
   */
  public function __wakeup() {
    $container = \Drupal::getContainer();
    foreach ($this->_serviceIds as $key => $service_id) {
      $this->$key = $container->get($service_id);
    }
    unset($this->_serviceIds);
  }

}
