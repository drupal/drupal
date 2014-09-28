<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldConfigInterface.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ThirdPartySettingsInterface;

/**
 * Defines an interface for configurable field definitions.
 *
 * This interface allows both configurable fields and overridden base fields to
 * share a common interface. The interface also extends ConfigEntityInterface
 * to ensure that implementations have the expected save() method.
 *
 * @see \Drupal\Core\Field\Entity\BaseFieldOverride
 * @see \Drupal\field\Entity\FieldConfig
 */
interface FieldConfigInterface extends FieldDefinitionInterface, ConfigEntityInterface, ThirdPartySettingsInterface {

  /**
   * Sets the field definition label.
   *
   * @param string $label
   *   The label to set.
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Sets whether the field is translatable.
   *
   * @param bool $translatable
   *   Whether the field is translatable.
   *
   * @return $this
   */
  public function setTranslatable($translatable);

  /**
   * Allows a bundle to be renamed.
   *
   * Renaming a bundle on the instance is allowed when an entity's bundle
   * is renamed and when field_entity_bundle_rename() does internal
   * housekeeping.
   */
  public function allowBundleRename();

  /**
   * Sets a default value.
   *
   * Note that if a default value callback is set, it will take precedence over
   * any value set here.
   *
   * @param mixed $value
   *   The default value in the format as returned by
   *   \Drupal\Core\Field\FieldDefinitionInterface::getDefaultValue().
   *
   * @return $this
   */
  public function setDefaultValue($value);

}
