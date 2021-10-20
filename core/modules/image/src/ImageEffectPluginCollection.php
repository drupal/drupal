<?php

namespace Drupal\image;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;

/**
 * A collection of image effects.
 */
class ImageEffectPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\image\ImageEffectInterface
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function sortHelper($aID, $bID) {
    return $this->get($aID)->getWeight() <=> $this->get($bID)->getWeight();
  }

}
