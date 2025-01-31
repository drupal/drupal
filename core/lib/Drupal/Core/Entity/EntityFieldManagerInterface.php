<?php

namespace Drupal\Core\Entity;

/**
 * Provides an interface for an entity field manager.
 */
interface EntityFieldManagerInterface {

  /**
   * Gets the base field definitions for a content entity type.
   *
   * Only fields that are not specific to a given bundle or set of bundles are
   * returned. This excludes configurable fields, as they are always attached
   * to a specific bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\FieldableEntityInterface are supported.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of base field definitions for the entity type, keyed by field
   *   name.
   *
   * @throws \LogicException
   *   Thrown if one of the entity keys is flagged as translatable.
   */
  public function getBaseFieldDefinitions($entity_type_id);

  /**
   * Gets the field definitions for a specific bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only entity types that implement
   *   \Drupal\Core\Entity\FieldableEntityInterface are supported.
   * @param string $bundle
   *   The bundle.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The array of field definitions for the bundle, keyed by field name.
   */
  public function getFieldDefinitions($entity_type_id, $bundle);

  /**
   * Gets the field storage definitions for a content entity type.
   *
   * This returns all field storage definitions for base fields and bundle
   * fields of an entity type. Note that field storage definitions of a base
   * field equal the full base field definition (i.e. they implement
   * FieldDefinitionInterface), while the storage definitions for bundle fields
   * may implement FieldStorageDefinitionInterface only.
   *
   * @param string $entity_type_id
   *   The entity type ID. Only content entities are supported.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   The array of field storage definitions for the entity type, keyed by
   *   field name.
   *
   * @see \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  public function getFieldStorageDefinitions($entity_type_id);

  /**
   * Gets a lightweight map of fields across bundles.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: An associative array of the bundles in which the field
   *     appears, where the keys and values are both the bundle's machine name.
   */
  public function getFieldMap();

  /**
   * Sets a lightweight map of fields across bundles.
   *
   * @param array[] $field_map
   *   See the return value of self::getFieldMap().
   *
   * @return $this
   */
  public function setFieldMap(array $field_map);

  /**
   * Gets a lightweight map of fields across bundles filtered by field type.
   *
   * @param string $field_type
   *   The field type to filter by.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: An associative array of the bundles in which the field
   *     appears, where the keys and values are both the bundle's machine name.
   */
  public function getFieldMapByFieldType($field_type);

  /**
   * Clears static and persistent field definition caches.
   */
  public function clearCachedFieldDefinitions();

  /**
   * Disable the use of caches.
   *
   * @param bool $use_caches
   *   FALSE to not use any caches.
   */
  public function useCaches($use_caches = FALSE);

  /**
   * Gets the "extra fields" for a bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle
   *   The bundle name.
   *
   * @return array
   *   A nested array of 'pseudo-field' elements. Each list is nested within the
   *   following keys: entity type, bundle name, context (either 'form' or
   *   'display'). The keys are the name of the elements as appearing in the
   *   renderable array (either the entity form or the displayed entity). The
   *   value is an associative array:
   *   - label: The human readable name of the element. Make sure you sanitize
   *     this appropriately.
   *   - description: A short description of the element contents.
   *   - weight: The default weight of the element.
   *   - visible: (optional) The default visibility of the element. Defaults to
   *     TRUE.
   *   - edit: (optional) String containing markup (normally a link) used as the
   *     element's 'edit' operation in the administration interface. Only for
   *     'form' context.
   *   - delete: (optional) String containing markup (normally a link) used as
   *     the element's 'delete' operation in the administration interface. Only
   *     for 'form' context.
   */
  public function getExtraFields($entity_type_id, $bundle);

  /**
   * Returns the labels used for a field on an entity type.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $field_name
   *   The machine name of the field.
   *
   * @return array
   *   An array where the first element is the most commonly used label for the
   *   field and the second element is a list of all labels in use. When more
   *   than one label is used the same number of times then the labels are
   *   sorted alphabetically.
   */
  public function getFieldLabels(string $entity_type, string $field_name): array;

}
