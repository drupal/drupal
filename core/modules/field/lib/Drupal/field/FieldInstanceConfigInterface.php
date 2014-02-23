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
   * @return \Drupal\field\FieldConfigInterface
   *   The field entity for this instance.
   */
  public function getField();

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

}
