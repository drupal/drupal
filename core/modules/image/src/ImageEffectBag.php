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
