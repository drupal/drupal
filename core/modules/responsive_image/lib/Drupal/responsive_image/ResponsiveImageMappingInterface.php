<?php

/**
 * @file
 * Contains \Drupal\responsive_image\Entity\ResponsiveImageMappingInterface.
 */

namespace Drupal\responsive_image;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a responsive image mapping entity.
 */
interface ResponsiveImageMappingInterface extends ConfigEntityInterface {

  /**
   * Checks if there's at least one mapping defined.
   */
  public function hasMappings();

}
