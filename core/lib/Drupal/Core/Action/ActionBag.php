<?php

/**
 * @file
 * Contains \Drupal\Core\Action\ActionBag.
 */

namespace Drupal\Core\Action;

use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides a container for lazily loading Action plugins.
 */
class ActionBag extends PluginBag {

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a new ActionBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $instance_ids
   *   The ids of the plugin instances with which we are dealing.
   * @param array $configuration
   *   An array of configuration.
   */
  public function __construct(PluginManagerInterface $manager, array $instance_ids, array $configuration) {
    $this->manager = $manager;
    $this->instanceIDs = drupal_map_assoc($instance_ids);
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (isset($this->pluginInstances[$instance_id])) {
      return;
    }

    $this->pluginInstances[$instance_id] = $this->manager->createInstance($instance_id, $this->configuration);
  }

}
