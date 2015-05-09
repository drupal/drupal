<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\LazyPluginCollection.
 */

namespace Drupal\Component\Plugin;

/**
 * Defines an object which stores multiple plugin instances to lazy load them.
 *
 * @ingroup plugin_api
 */
abstract class LazyPluginCollection implements \IteratorAggregate, \Countable {

  /**
   * Stores all instantiated plugins.
   *
   * @var array
   */
  protected $pluginInstances = array();

  /**
   * Stores the IDs of all potential plugin instances.
   *
   * @var array
   */
  protected $instanceIDs = array();

  /**
   * Initializes and stores a plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance to initialize.
   */
  abstract protected function initializePlugin($instance_id);

  /**
   * Gets the current configuration of all plugins in this collection.
   *
   * @return array
   *   An array of up-to-date plugin configuration.
   */
  abstract public function getConfiguration();

  /**
   * Sets the configuration for all plugins in this collection.
   *
   * @param array $configuration
   *   An array of up-to-date plugin configuration.
   *
   * @return $this
   */
  abstract public function setConfiguration($configuration);

  /**
   * Clears all instantiated plugins.
   */
  public function clear() {
    $this->pluginInstances = array();
  }

  /**
   * Determines if a plugin instance exists.
   *
   * @param string $instance_id
   *   The ID of the plugin instance to check.
   *
   * @return bool
   *   TRUE if the plugin instance exists, FALSE otherwise.
   */
  public function has($instance_id) {
    return isset($this->pluginInstances[$instance_id]) || isset($this->instanceIDs[$instance_id]);
  }

  /**
   * Gets a plugin instance, initializing it if necessary.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being retrieved.
   */
  public function &get($instance_id) {
    if (!isset($this->pluginInstances[$instance_id])) {
      $this->initializePlugin($instance_id);
    }
    return $this->pluginInstances[$instance_id];
  }

  /**
   * Stores an initialized plugin.
   *
   * @param string $instance_id
   *   The ID of the plugin instance being stored.
   * @param mixed $value
   *   An instantiated plugin.
   */
  public function set($instance_id, $value) {
    $this->pluginInstances[$instance_id] = $value;
    $this->addInstanceId($instance_id);
  }

  /**
   * Removes an initialized plugin.
   *
   * The plugin can still be used; it will be reinitialized.
   *
   * @param string $instance_id
   *   The ID of the plugin instance to remove.
   */
  public function remove($instance_id) {
    unset($this->pluginInstances[$instance_id]);
  }

  /**
   * Adds an instance ID to the available instance IDs.
   *
   * @param string $id
   *   The ID of the plugin instance to add.
   * @param array|null $configuration
   *   (optional) The configuration used by this instance. Defaults to NULL.
   */
  public function addInstanceId($id, $configuration = NULL) {
    if (!isset($this->instanceIDs[$id])) {
      $this->instanceIDs[$id] = $id;
    }
  }

  /**
   * Gets all instance IDs.
   *
   * @return array
   *   An array of all available instance IDs.
   */
  public function getInstanceIds() {
    return $this->instanceIDs;
  }

  /**
   * Removes an instance ID.
   *
   * @param string $instance_id
   *   The ID of the plugin instance to remove.
   */
  public function removeInstanceId($instance_id) {
    unset($this->instanceIDs[$instance_id]);
    $this->remove($instance_id);
  }

  public function getIterator() {
    $instances = [];
    foreach ($this->getInstanceIds() as $instance_id) {
      $instances[$instance_id] = $this->get($instance_id);
    }
    return new \ArrayIterator($instances);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->instanceIDs);
  }

}
