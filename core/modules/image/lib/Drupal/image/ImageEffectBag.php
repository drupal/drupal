<?php

/**
 * @file
 * Contains \Drupal\image\ImageEffectBag.
 */

namespace Drupal\image;

use Drupal\Component\Plugin\PluginBag;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\MapArray;
use Drupal\Component\Uuid\Uuid;

/**
 * A collection of image effects.
 */
class ImageEffectBag extends PluginBag {

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * The initial configuration for each image effect in the bag.
   *
   * @var array
   */
  protected $configurations = array();

  /**
   * Constructs a new ImageEffectBag.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param array $configurations
   *   (optional) An associative array containing the initial configuration for
   *   each tour in the bag, keyed by plugin instance ID.
   */
  public function __construct(PluginManagerInterface $manager, array $configurations = array()) {
    $this->manager = $manager;
    $this->configurations = $configurations;

    if (!empty($configurations)) {
      $this->instanceIDs = MapArray::copyValuesToKeys(array_keys($configurations));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!isset($this->pluginInstances[$instance_id])) {
      $configuration = $this->configurations[$instance_id] + array('data' => array());
      $this->pluginInstances[$instance_id] = $this->manager->createInstance($configuration['id'], $configuration);
    }
  }

  /**
   * Returns the current configuration of all image effects in this bag.
   *
   * @return array
   *   An associative array keyed by image effect UUID, whose values are image
   *   effect configurations.
   */
  public function export() {
    $instances = array();
    $this->rewind();
    foreach ($this as $instance_id => $instance) {
      $instances[$instance_id] = $instance->export();
    }
    return $instances;
  }

  /**
   * Removes an instance ID.
   *
   * @param string $instance_id
   *   An image effect instance IDs.
   */
  public function removeInstanceID($instance_id) {
    unset($this->instanceIDs[$instance_id], $this->configurations[$instance_id]);
    $this->remove($instance_id);
  }

  /**
   * Updates the configuration for an image effect instance.
   *
   * If there is no plugin instance yet, a new will be instantiated. Otherwise,
   * the existing instance is updated with the new configuration.
   *
   * @param array $configuration
   *   The image effect configuration to set.
   *
   * @return string
   */
  public function setConfig(array $configuration) {
    // Derive the instance ID from the configuration.
    if (empty($configuration['uuid'])) {
      $uuid_generator = new Uuid();
      $configuration['uuid'] = $uuid_generator->generate();
    }
    $instance_id = $configuration['uuid'];
    $this->configurations[$instance_id] = $configuration;
    $this->get($instance_id)->setPluginConfiguration($configuration);
    $this->addInstanceID($instance_id);
    return $instance_id;
  }

  /**
   * Sorts all image effect instances in this bag.
   *
   * @return self
   */
  public function sort() {
    uasort($this->configurations, 'drupal_sort_weight');
    $this->instanceIDs = MapArray::copyValuesToKeys(array_keys($this->configurations));
    return $this;
  }

}
