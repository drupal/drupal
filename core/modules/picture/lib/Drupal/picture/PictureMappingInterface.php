<?php

/**
 * @file
 * Contains \Drupal\picture\Plugin\Core\Entity\PictureMappingInterface.
 */

namespace Drupal\picture;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a picture mapping entity.
 */
interface PictureMappingInterface extends ConfigEntityInterface {

  /**
   * Checks if there's at least one mapping defined.
   */
  public function hasMappings();

}
