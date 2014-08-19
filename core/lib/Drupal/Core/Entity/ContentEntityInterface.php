<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\ContentEntityInterface.
 */

namespace Drupal\Core\Entity;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Defines a common interface for all content entity objects.
 *
 * This interface builds upon the general interfaces provided by the typed data
 * API, while extending them with content entity-specific additions. I.e., a
 * content entity implements the ComplexDataInterface among others, thus is
 * complex data containing fields as its data properties. The contained fields
 * have to implement \Drupal\Core\Field\FieldItemListInterface,
 * which builds upon typed data interfaces as well.
 *
 * When implementing this interface which extends Traversable, make sure to list
 * IteratorAggregate or Iterator before this interface in the implements clause.
 *
 * @see \Drupal\Core\TypedData\TypedDataManager
 * @see \Drupal\Core\Field\FieldItemListInterface
 *
 * @ingroup entity_api
 */
interface ContentEntityInterface extends EntityInterface, RevisionableInterface, TranslatableInterface, ComplexDataInterface {

  /**
   * Marks the translation identified by the given language code as existing.
   *
   * @param string $langcode
   *   The language code identifying the translation to be initialized.
   *
   * @todo Remove this as soon as translation metadata have been converted to
   *    regular fields.
   */
  public function initTranslation($langcode);

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
   * \Drupal\Core\Entity\ContentEntityInterface::bundleFieldDefinitions().
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
   * @see \Drupal\Core\Entity\ContentEntityInterface::bundleFieldDefinitions()
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
   * @see \Drupal\Core\Entity\ContentEntityInterface::baseFieldDefinitions()
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions);

  /**
   * Returns whether the entity has a field with the given name.
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
   * Returns an array of all field values.
   *
   * Gets an array of plain field values, including only non-computed values.
   * Note that the structure varies by entity type and bundle.
   *
   * @return array
   *   An array of field values, keyed by field name.
   */
  public function toArray();

}
