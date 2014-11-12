<?php

/**
 * @file
 * Contains \Drupal\field\FieldStorageConfigInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides an interface defining a field storage entity.
 */
interface FieldStorageConfigInterface extends ConfigEntityInterface, FieldStorageDefinitionInterface {

  /**
   * Returns the list of bundles where the field storage has fields.
   *
   * @return array
   *   An array of bundle names.
   */
  public function getBundles();

  /**
   * Returns whether the field storage is locked or not.
   *
   * @return bool
   *   TRUE if the field storage is locked.
   */
  public function isLocked();

  /**
   * Checks if the field storage can be deleted.
   *
   * @return bool
   *   TRUE if the field storage can be deleted.
   */
  public function isDeletable();

}
