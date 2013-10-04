<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\Field\FieldDefinitionInterface;

/**
 * Provides an interface defining a field instance entity.
 */
interface FieldInstanceInterface extends ConfigEntityInterface, FieldDefinitionInterface {

  /**
   * Returns the field entity for this instance.
   *
   * @return \Drupal\field\FieldInterface
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

}
