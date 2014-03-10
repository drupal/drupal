<?php

/**
 * @file
 * Contains \Drupal\Core\Plugin\DefaultSinglePluginBag.
 */

namespace Drupal\Core\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\ConfigurablePluginInterface;

/**
 * Provides a default plugin bag for a plugin type.
 *
 * A plugin bag usually stores multiple plugins, and is used to lazily
 * instantiate them. When only one plugin is needed, it is still best practice
 * to encapsulate all of the instantiation logic in a plugin bag. This class can
 * be used directly, or subclassed to add further exception handling in
 * self::initializePlugin().
 */
class DefaultSinglePluginBag extends PluginBag {

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * An array of configuration to instantiate the plugin with.
   *
   * @var array
   */
  protected $configuration;

  /**
   * The instance ID used for this plugin bag.
   *
   * @var string
   */
  protected $instanceId;

  /**
   * Constructs a new DefaultSinglePluginBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration) {
    $this->manager = $manager;
    $this->instanceId = $instance_id;
    // This is still needed by the parent PluginBag class.
    $this->instanceIDs = array($instance_id => $instance_id);
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $this->set($instance_id, $this->manager->createInstance($instance_id, $this->configuration));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $plugin = $this->get($this->instanceId);
    if ($plugin instanceof ConfigurablePluginInterface) {
      return $plugin->getConfiguration();
    }
    else {
      return $this->configuration;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration($configuration) {
    $plugin = $this->get($this->instanceId);
    if ($plugin instanceof ConfigurablePluginInterface) {
      $plugin->setConfiguration($configuration);
    }
    $this->configuration = $configuration;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addInstanceId($id, $configuration = NULL) {
    parent::addInstanceId($id, $configuration);
    if ($configuration !== NULL) {
      $this->setConfiguration($configuration);
    }
  }

}
