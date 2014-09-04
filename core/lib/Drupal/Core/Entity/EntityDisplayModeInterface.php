<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\EntityDisplayModeInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for entity types that hold form and view mode settings.
 */
interface EntityDisplayModeInterface extends ConfigEntityInterface {

  /**
   * Returns the entity type this display mode is used for.
   *
   * @return string
   *   The entity type name.
   */
  public function getTargetType();

}
