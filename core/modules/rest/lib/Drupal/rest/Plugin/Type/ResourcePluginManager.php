<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\Type\ResourcePluginManager.
 */

namespace Drupal\rest\Plugin\Type;

use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Factory\ReflectionFactory;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Manages discovery and instantiation of resource plugins.
 */
class ResourcePluginManager extends PluginManagerBase {

  /**
   * Overrides Drupal\Component\Plugin\PluginManagerBase::__construct().
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations,
   */
  public function __construct(\Traversable $namespaces) {
    // Create resource plugin derivatives from declaratively defined resources.
    $this->discovery = new AnnotatedClassDiscovery('rest/resource', $namespaces);
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new AlterDecorator($this->discovery, 'rest_resource');
    $this->discovery = new CacheDecorator($this->discovery, 'rest');

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
