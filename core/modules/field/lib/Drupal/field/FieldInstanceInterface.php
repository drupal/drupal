<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a field instance entity.
 */
interface FieldInstanceInterface extends ConfigEntityInterface, \ArrayAccess, \Serializable {

  /**
   * Returns the field entity for this instance.
   *
   * @return \Drupal\field\FieldInterface
   *   The field entity for this instance.
   */
  public function getField();

  /**
   * Returns the Widget plugin for the instance.
   *
   * @return Drupal\field\Plugin\Type\Widget\WidgetInterface
   *   The Widget plugin to be used for the instance.
   */
  public function getWidget();

  /**
   * Allows a bundle to be renamed.
   *
   * Renaming a bundle on the instance is allowed when an entity's bundle
   * is renamed and when field_entity_bundle_rename() does internal
   * housekeeping.
   */
  public function allowBundleRename();

}
