<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\PluginManagerBase.
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;

/**
 * Base class for plugin managers.
 */
abstract class PluginManagerBase implements PluginManagerInterface {

  use DiscoveryTrait;

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
   * Gets the plugin discovery.
   *
   * @return \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected function getDiscovery() {
    return $this->discovery;
  }

  /**
   * Gets the plugin factory.
   *
   * @return \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected function getFactory() {
    return $this->factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->getDiscovery()->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    return $this->getDiscovery()->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = array()) {
    // If this PluginManager has fallback capabilities catch
    // PluginNotFoundExceptions.
    if ($this instanceof FallbackPluginManagerInterface) {
      try {
        return $this->getFactory()->createInstance($plugin_id, $configuration);
      }
      catch (PluginNotFoundException $e) {
        $fallback_id = $this->getFallbackPluginId($plugin_id, $configuration);
        return $this->getFactory()->createInstance($fallback_id, $configuration);
      }
    }
    else {
      return $this->getFactory()->createInstance($plugin_id, $configuration);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getInstance(array $options) {
    return $this->mapper->getInstance($options);
  }

}
