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
   * Implementations typically use the class \Drupal\Core\Field\FieldDefinition
   * for creating the field definitions; for example a 'name' field could be
   * defined as the following:
   * @code
   * $fields['name'] = FieldDefinition::create('string')
   *   ->setLabel(t('Name'));
   * @endcode
   *
   * If some elements in a field definition need to vary by bundle, use
   * \Drupal\Core\Entity\ContentEntityInterface::bundleFieldDefinitions().
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
   * Provides or alters field definitions for a specific bundle.
   *
   * The field definitions returned here for the bundle take precedence on the
   * base field definitions specified by baseFieldDefinitions() for the entity
   * type.
   *
   * @todo Provide a better DX for field overrides.
   *   See https://drupal.org/node/2145115.
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
