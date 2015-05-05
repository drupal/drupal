<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldConfigInterface.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

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
interface FieldConfigInterface extends FieldDefinitionInterface, ConfigEntityInterface {

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
   * Sets a human readable description.
   *
   * Descriptions are usually used on user interfaces where the data is edited
   * or displayed.
   *
   * @param string $description
   *   The description for this field.
   *
   * @return $this
   */
  public function setDescription($description);

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
   * Sets field settings (overwrites existing settings).
   *
   * @param array $settings
   *   The array of field settings.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Sets the value for a field setting by name.
   *
   * @param string $setting_name
   *   The name of the setting.
   * @param mixed $value
   *   The value of the setting.
   *
   * @return $this
   */
  public function setSetting($setting_name, $value);

  /**
   * Sets whether the field can be empty.
   *
   * If a field is required, an entity needs to have at least a valid,
   * non-empty item in that field's FieldItemList in order to pass validation.
   *
   * An item is considered empty if its isEmpty() method returns TRUE.
   * Typically, that is if at least one of its required properties is empty.
   *
   * @param bool $required
   *   TRUE if the field is required. FALSE otherwise.
   *
   * @return $this
   *   The current object, for a fluent interface.
   */
  public function setRequired($required);

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

  /**
   * Sets constraints for a given field item property.
   *
   * Note: this overwrites any existing property constraints. If you need to
   * add to the existing constraints, use
   * \Drupal\Core\Field\FieldConfigInterface::addPropertyConstraints()
   *
   * Note that constraints added via this method are not stored in configuration
   * and as such need to be added at runtime using
   * hook_entity_bundle_field_info_alter().
   *
   * @param string $name
   *   The name of the property to set constraints for.
   * @param array $constraints
   *   The constraints to set.
   *
   * @return static
   *   The object itself for chaining.
   *
   * @see hook_entity_bundle_field_info_alter()
   */
  public function setPropertyConstraints($name, array $constraints);

  /**
   * Adds constraints for a given field item property.
   *
   * Adds a constraint to a property of a field item. e.g.
   * @code
   * // Limit the field item's value property to the range 0 through 10.
   * // e.g. $node->field_how_many->value.
   * $field->addPropertyConstraints('value', [
   *   'Range' => [
   *     'min' => 0,
   *     'max' => 10,
   *   ]
   * ]);
   * @endcode
   *
   * If you want to add a validation constraint that applies to the
   * \Drupal\Core\Field\FieldItemList, use FieldConfigInterface::addConstraint()
   * instead.
   *
   * Note: passing a new set of options for an existing property constraint will
   * overwrite with the new options.
   *
   * Note that constraints added via this method are not stored in configuration
   * and as such need to be added at runtime using
   * hook_entity_bundle_field_info_alter().
   *
   * @param string $name
   *   The name of the property to set constraints for.
   * @param array $constraints
   *   The constraints to set.
   *
   * @return static
   *   The object itself for chaining.
   *
   * @see \Drupal\Core\Field\FieldConfigInterface::addConstraint()
   * @see hook_entity_bundle_field_info_alter()
   */
  public function addPropertyConstraints($name, array $constraints);

  /**
   * Adds a validation constraint to the FieldItemList.
   *
   * Note: If you wish to apply a constraint to just a property of a FieldItem
   * use \Drupal\Core\Field\FieldConfigInterface::addPropertyConstraints()
   * instead.
   * @code
   *   // Add a constraint to the 'field_username' FieldItemList.
   *   // e.g. $node->field_username
   *   $fields['field_username']->addConstraint('UserNameUnique', []);
   * @endcode
   *
   * If you wish to apply a constraint to a \Drupal\Core\Field\FieldItem instead
   * of a property or FieldItemList, you can use the
   * \Drupal\Core\Field\FieldConfigBase::getItemDefinition() method.
   * @code
   *   // Add a constraint to the 'field_entity_reference' FieldItem (entity
   *   // reference item).
   *   $fields['field_entity_reference']->getItemDefinition()->addConstraint('MyCustomFieldItemValidationPlugin', []);
   * @endcode
   *
   * See \Drupal\Core\TypedData\DataDefinitionInterface::getConstraints() for
   * details.
   *
   * Note that constraints added via this method are not stored in configuration
   * and as such need to be added at runtime using
   * hook_entity_bundle_field_info_alter().
   *
   * @param string $constraint_name
   *   The name of the constraint to add, i.e. its plugin id.
   * @param array|null $options
   *   The constraint options as required by the constraint plugin, or NULL.
   *
   * @return static
   *   The object itself for chaining.
   *
   * @see \Drupal\Core\Field\FieldItemList
   * @see \Drupal\Core\Field\FieldConfigInterface::addPropertyConstraints()
   * @see hook_entity_bundle_field_info_alter()
   */
  public function addConstraint($constraint_name, $options = NULL);

  /**
   * Sets the array of validation constraints for the FieldItemList.
   *
   * NOTE: This will overwrite any previously set constraints. In most cases
   * FieldConfigInterface::addConstraint() should be used instead.
   *
   * Note that constraints added via this method are not stored in configuration
   * and as such need to be added at runtime using
   * hook_entity_bundle_field_info_alter().
   *
   * @param array $constraints
   *   The array of constraints. See
   *   \Drupal\Core\TypedData\TypedDataManager::getConstraints() for details.
   *
   * @return $this
   *
   * @see \Drupal\Core\TypedData\DataDefinition::addConstraint()
   * @see \Drupal\Core\TypedData\DataDefinition::getConstraints()
   * @see \Drupal\Core\Field\FieldItemList
   * @see hook_entity_bundle_field_info_alter()
   */
  public function setConstraints(array $constraints);

  /**
   * Allows a bundle to be renamed.
   *
   * Renaming a bundle on the instance is allowed when an entity's bundle
   * is renamed and when field_entity_bundle_rename() does internal
   * housekeeping.
   */
  public function allowBundleRename();

}
