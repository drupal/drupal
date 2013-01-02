<?php

/**
 * @file
 * Contains \Drupal\plugin_test\Plugin\TestPluginBag.
 */

namespace Drupal\plugin_test\Plugin;

use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines a plugin bag which uses fruit plugins.
 */
class TestPluginBag extends PluginBag {

  /**
   * Stores the plugin manager used by this bag.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a TestPluginBag object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager that handles test plugins.
   */
  public function __construct(PluginManagerInterface $manager) {
    $this->manager = $manager;

    $this->instanceIDs = drupal_map_assoc(array_keys($this->manager->getDefinitions()));
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginBag::initializePlugin().
   */
  protected function initializePlugin($instance_id) {
    // If the plugin was initialized before, just return.
    if (isset($this->pluginInstances[$instance_id])) {
      return;
    }

    $this->pluginInstances[$instance_id] = $this->manager->createInstance($instance_id, array());
  }

}
