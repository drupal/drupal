<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\DefaultSinglePluginBag.
 */

namespace Drupal\Component\Plugin;

use Drupal\Component\Utility\MapArray;

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
   * Constructs a new DefaultSinglePluginBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $instance_ids
   *   The IDs of the plugin instances with which we are dealing.
   * @param array $configuration
   *   An array of configuration.
   */
  public function __construct(PluginManagerInterface $manager, array $instance_ids, array $configuration) {
    $this->manager = $manager;
    $this->instanceIDs = MapArray::copyValuesToKeys($instance_ids);
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $this->pluginInstances[$instance_id] = $this->manager->createInstance($instance_id, $this->configuration);
  }

}
