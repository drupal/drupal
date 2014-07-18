<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceConfigInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides an interface defining a field instance entity.
 */
interface FieldInstanceConfigInterface extends ConfigEntityInterface, FieldDefinitionInterface {

  /**
   * Returns the field entity for this instance.
   *
   * @return \Drupal\field\FieldStorageConfigInterface
   *   The field storage entity for this instance.
   */
  public function getFieldStorageDefinition();

  /**
   * Allows a bundle to be renamed.
   *
   * Renaming a bundle on the instance is allowed when an entity's bundle
   * is renamed and when field_entity_bundle_rename() does internal
   * housekeeping.
   */
  public function allowBundleRename();

  /**
   * Returns the name of the bundle this field instance is attached to.
   *
   * @return string
   *   The name of the bundle this field instance is attached to.
   */
  public function targetBundle();

  /**
   * Gets the deleted flag of the field instance.
   *
   * @return bool
   *   Returns TRUE if the instance is deleted.
   */
  public function isDeleted();

}
