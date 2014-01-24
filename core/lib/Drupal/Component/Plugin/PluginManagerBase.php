<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\PluginManagerBase
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;

/**
 * Base class for plugin managers.
 */
abstract class PluginManagerBase implements PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * The object that discovers plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The object that instantiates plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $factory;

  /**
   * The object that returns the preconfigured plugin instance appropriate for a particular runtime condition.
   *
   * @var \Drupal\Component\Plugin\Mapper\MapperInterface
   */
  protected $mapper;

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id) {
    return $this->discovery->getDefinition($plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->discovery->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    if ($this->discovery instanceof CachedDiscoveryInterface) {
      $this->discovery->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    return $this->factory->createInstance($plugin_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->mapper->getInstance($options);
  }

}
