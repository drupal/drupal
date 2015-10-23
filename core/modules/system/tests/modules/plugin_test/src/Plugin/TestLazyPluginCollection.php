<?php

/**
 * @file
 * Contains \Drupal\plugin_test\Plugin\TestLazyPluginCollection.
 */

namespace Drupal\plugin_test\Plugin;

use Drupal\Component\Plugin\LazyPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines a plugin collection which uses fruit plugins.
 */
class TestLazyPluginCollection extends LazyPluginCollection {

  /**
   * Stores the plugin manager used by this collection.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * Constructs a TestLazyPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The plugin manager that handles test plugins.
   */
  public function __construct(PluginManagerInterface $manager) {
    $this->manager = $manager;

    $instance_ids = array_keys($this->manager->getDefinitions());
    $this->instanceIDs = array_combine($instance_ids, $instance_ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    $this->pluginInstances[$instance_id] = $this->manager->createInstance($instance_id, array());
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration($configuration) {
    return $this;
  }

}
