<?php

/**
 * @file
 * Contains \Drupal\Component\Plugin\PluginBag.
 */

namespace Drupal\Component\Plugin;

/**
 * Defines an object which stores multiple plugin instances to lazy load them.
 */
abstract class PluginBag implements \Iterator, \Countable {

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
   * Returns the current configuration of all plugins in this bag.
   *
   * @return array
   *   An array of up-to-date plugin configuration.
   */
  abstract public function getConfiguration();

  /**
   * Sets the configuration for all plugins in this bag.
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
   * Retrieves a plugin instance, initializing it if necessary.
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
   * Returns all instance IDs.
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

  /**
   * {@inheritdoc}
   */
  public function current() {
    return $this->get($this->key());
  }

  /**
   * {@inheritdoc}
   */
  public function next() {
    next($this->instanceIDs);
  }

  /**
   * {@inheritdoc}
   */
  public function key() {
    return key($this->instanceIDs);
  }

  /**
   * {@inheritdoc}
   */
  public function valid() {
    $key = key($this->instanceIDs);
    // Check the key is valid but also that this key yields a plugin from get().
    // There can be situations where configuration contains data for a plugin
    // that cannot be instantiated. In this case, this enables us to skip that
    // plugin during iteration.
    // @todo Look at removing when https://drupal.org/node/2080823 has been
    //   solved.
    return $key !== NULL && $key !== FALSE && $this->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function rewind() {
    reset($this->instanceIDs);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->instanceIDs);
  }

}
