<?php

/**
 * @file
 * Contains \Drupal\field\FieldConfigInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides an interface defining a field entity.
 */
interface FieldConfigInterface extends ConfigEntityInterface, FieldDefinitionInterface {

  /**
   * Returns the list of bundles where the field has instances.
   *
   * @return array
   *   An array of bundle names.
   */
  public function getBundles();

  /**
   * Returns whether the field is locked or not.
   *
   * @return bool
   *   TRUE if the field is locked.
   */
  public function isLocked();

}
