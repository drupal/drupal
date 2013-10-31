<?php

/**
 * @file
 * Contains \Drupal\image\ImageEffectBag.
 */

namespace Drupal\image;

use Drupal\Component\Utility\MapArray;
use Drupal\Component\Plugin\DefaultPluginBag;

/**
 * A collection of image effects.
 */
class ImageEffectBag extends DefaultPluginBag {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\image\ImageEffectInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
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
   *   The image effect UUID.
   */
  public function updateConfiguration(array $configuration) {
    // Derive the instance ID from the configuration.
    if (empty($configuration['uuid'])) {
      $uuid_generator = \Drupal::service('uuid');
      $configuration['uuid'] = $uuid_generator->generate();
    }
    $instance_id = $configuration['uuid'];
    $this->setConfiguration($instance_id, $configuration);
    $this->addInstanceId($instance_id);
    return $instance_id;
  }

  /**
   * {@inheritdoc}
   */
  public function sort() {
    uasort($this->configurations, 'drupal_sort_weight');
    $this->instanceIDs = MapArray::copyValuesToKeys(array_keys($this->configurations));
    return $this;
  }

}
