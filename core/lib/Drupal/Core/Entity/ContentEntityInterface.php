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
   * Defines the base fields of the entity type.
   *
   * @param string $entity_type
   *   The entity type to return properties for. Useful when a single class is
   *   used for multiple, possibly dynamic entity types.
   *
   * @return array
   *   An array of entity field definitions as specified by
   *   \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions(), keyed by field
   *   name.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldDefinitions()
   */
  public static function baseFieldDefinitions($entity_type);

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

}
