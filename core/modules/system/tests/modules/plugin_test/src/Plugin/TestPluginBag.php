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

    $instance_ids = array_keys($this->manager->getDefinitions());
    $this->instanceIDs = array_combine($instance_ids, $instance_ids);
  }

  /**
   * Implements \Drupal\Component\Plugin\PluginBag::initializePlugin().
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
