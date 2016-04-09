<?php

namespace Drupal\Core\Entity;

/**
 * Interface for entities having fields.
 *
 * This interface builds upon the general interfaces provided by the typed data
 * API, while extending them with entity-specific additions. I.e., fieldable
 * entities implement the ComplexDataInterface among others, thus it is complex
 * data containing fields as its data properties. The contained fields have to
 * implement \Drupal\Core\Field\FieldItemListInterface, which builds upon typed
 * data interfaces as well.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager
 * @see \Drupal\Core\Field\FieldItemListInterface
 *
 * @ingroup entity_api
 */
interface FieldableEntityInterface extends EntityInterface {

  /**
   * Provides base field definitions for an entity type.
   *
   * Implementations typically use the class
   * \Drupal\Core\Field\BaseFieldDefinition for creating the field definitions;
   * for example a 'name' field could be defined as the following:
   * @code
   * $fields['name'] = BaseFieldDefinition::create('string')
   *   ->setLabel(t('Name'));
   * @endcode
   *
   * By definition, base fields are fields that exist for every bundle. To
   * provide definitions for fields that should only exist on some bundles, use
   * \Drupal\Core\Entity\FieldableEntityInterface::bundleFieldDefinitions().
   *
   * The definitions returned by this function can be overridden for all
   * bundles by hook_entity_base_field_info_alter() or overridden on a
   * per-bundle basis via 'base_field_override' configuration entities.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of base field definitions for the entity type, keyed by field
   *   name.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions()
   * @see \Drupal\Core\Entity\FieldableEntityInterface::bundleFieldDefinitions()
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type);

  /**
   * Provides field definitions for a specific bundle.
   *
   * This function can return definitions both for bundle fields (fields that
   * are not defined in $base_field_definitions, and therefore might not exist
   * on some bundles) as well as bundle-specific overrides of base fields
   * (fields that are defined in $base_field_definitions, and therefore exist
   * for all bundles). However, bundle-specific base field overrides can also
   * be provided by 'base_field_override' configuration entities, and that is
   * the recommended approach except in cases where an entity type needs to
   * provide a bundle-specific base field override that is decoupled from
   * configuration. Note that for most entity types, the bundles themselves are
   * derived from configuration (e.g., 'node' bundles are managed via
   * 'node_type' configuration entities), so decoupling bundle-specific base
   * field overrides from configuration only makes sense for entity types that
   * also decouple their bundles from configuration. In cases where both this
   * function returns a bundle-specific override of a base field and a
   * 'base_field_override' configuration entity exists, the latter takes
   * precedence.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition. Useful when a single class is used for multiple,
   *   possibly dynamic entity types.
   * @param string $bundle
   *   The bundle.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $base_field_definitions
   *   The list of base field definitions.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of bundle field definitions, keyed by field name.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions()
   * @see \Drupal\Core\Entity\FieldableEntityInterface::baseFieldDefinitions()
   *
   * @todo WARNING: This method will be changed in
   *   https://www.drupal.org/node/2346347.
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions);

  /**
   * Determines whether the entity has a field with the given name.
   *
   * @param string $field_name
   *   The field name.
   *
   * @return bool
   *   TRUE if the entity has a field with the given name. FALSE otherwise.
   */
  public function hasField($field_name);

  /**
   * Gets the definition of a contained field.
   *
   * @param string $name
   *   The name of the field.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The definition of the field or null if the field does not exist.
   */
  public function getFieldDefinition($name);

  /**
   * Gets an array of field definitions of all contained fields.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definitions, keyed by field name.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions()
   */
  public function getFieldDefinitions();

  /**
   * Gets an array of all field values.
   *
   * Gets an array of plain field values, including only non-computed values.
   * Note that the structure varies by entity type and bundle.
   *
   * @return array
   *   An array of field values, keyed by field name.
   */
  public function toArray();

  /**
   * Gets a field item list.
   *
   * @param string $field_name
   *   The name of the field to get; e.g., 'title' or 'name'.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   The field item list, containing the field items.
   *
   * @throws \InvalidArgumentException
   *   If an invalid field name is given.
   */
  public function get($field_name);

  /**
   * Sets a field value.
   *
   * @param string $field_name
   *   The name of the field to set; e.g., 'title' or 'name'.
   * @param mixed $value
   *   The value to set, or NULL to unset the field.
   * @param bool $notify
   *   (optional) Whether to notify the entity of the change. Defaults to
   *   TRUE. If the update stems from the entity, set it to FALSE to avoid
   *   being notified again.
   *
   * @return $this
   *
   * @throws \InvalidArgumentException
   *   If the specified field does not exist.
   */
  public function set($field_name, $value, $notify = TRUE);

  /**
   * Gets an array of all field item lists.
   *
   * @param bool $include_computed
   *   If set to TRUE, computed fields are included. Defaults to TRUE.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   An array of field item lists implementing, keyed by field name.
   */
  public function getFields($include_computed = TRUE);

  /**
   * Gets an array of field item lists for translatable fields.
   *
   * @param bool $include_computed
   *   If set to TRUE, computed fields are included. Defaults to TRUE.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface[]
   *   An array of field item lists implementing, keyed by field name.
   */
  public function getTranslatableFields($include_computed = TRUE);

  /**
   * Reacts to changes to a field.
   *
   * Note that this is invoked after any changes have been applied.
   *
   * @param string $field_name
   *   The name of the field which is changed.
   *
   * @throws \InvalidArgumentException
   *   When trying to assign a value to the language field that matches an
   *   existing translation.
   * @throws \LogicException
   *   When trying to change:
   *   - The language of a translation.
   *   - The value of the flag identifying the default translation object.
   */
  public function onChange($field_name);

  /**
   * Validates the currently set values.
   *
   * @return \Drupal\Core\Entity\EntityConstraintViolationListInterface
   *   A list of constraint violations. If the list is empty, validation
   *   succeeded.
   */
  public function validate();

  /**
   * Checks whether entity validation is required before saving the entity.
   *
   * @return bool
   *   TRUE if validation is required, FALSE if not.
   */
  public function isValidationRequired();

  /**
   * Sets whether entity validation is required before saving the entity.
   *
   * @param bool $required
   *   TRUE if validation is required, FALSE otherwise.
   *
   * @return $this
   */
  public function setValidationRequired($required);

}
