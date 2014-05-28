<?php

/**
 * @file
 * Contains \Drupal\image\ImageEffectBag.
 */

namespace Drupal\image;

use Drupal\Core\Plugin\DefaultPluginBag;

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
    $this->addInstanceId($instance_id, $configuration);
    return $instance_id;
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    $a_weight = $this->get($aID)->getWeight();
    $b_weight = $this->get($bID)->getWeight();
    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

}
