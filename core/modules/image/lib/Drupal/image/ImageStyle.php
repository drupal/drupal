<?php

/**
 * @file
 * Definition of Drupal\image\ImageStyle.
 */

namespace Drupal\image;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines an image style configuration entity.
 */
class ImageStyle extends ConfigEntityBase {

  /**
   * The name of the image style.
   *
   * @var string
   */
  public $name;

  /**
   * The image style label.
   *
   * @var string
   */
  public $label;

  /**
   * The array of image effects for this image style.
   *
   * @var string
   */
  public $effects;


  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

}
