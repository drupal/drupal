<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\Type\ResourcePluginManager.
 */

namespace Drupal\rest\Plugin\Type;

use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Component\Plugin\Factory\ReflectionFactory;

/**
 * Manages discovery and instantiation of resource plugins.
 */
class ResourcePluginManager extends PluginManagerBase {

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param array $namespaces
   *   An array of paths keyed by it's corresponding namespaces.
   */
  public function __construct($namespaces) {
    // Create resource plugin derivatives from declaratively defined resources.
    $this->discovery = new DerivativeDiscoveryDecorator(new AnnotatedClassDiscovery('rest', 'resource', $namespaces));
    $this->factory = new ReflectionFactory($this->discovery);
  }

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::getInstance().
   */
  public function getInstance(array $options){
    if (isset($options['id'])) {
      return $this->createInstance($options['id']);
    }
  }
}
