<?php

namespace Drupal\Core\Field;

/**
 * Provides an interface for a deleted fields repository.
 *
 * @internal
 */
interface DeletedFieldsRepositoryInterface {

  /**
   * Returns a list of deleted field definitions.
   *
   * @param string $field_storage_unique_id
   *   (optional) A unique ID of field storage definition for filtering the
   *   deleted fields. Defaults to NULL.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   An array of field definition objects, keyed by their unique identifier.
   */
  public function getFieldDefinitions($field_storage_unique_id = NULL);

  /**
   * Returns a list of deleted field storage definitions.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface[]
   *   An array of field storage definition objects, keyed by their unique
   *   storage identifier.
   */
  public function getFieldStorageDefinitions();

  /**
   * Adds a field definition object to the deleted list.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   A field definition object.
   *
   * @return $this
   */
  public function addFieldDefinition(FieldDefinitionInterface $field_definition);

  /**
   * Adds a field storage definition object to the deleted list.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   A field storage definition object.
   *
   * @return $this
   */
  public function addFieldStorageDefinition(FieldStorageDefinitionInterface $field_storage_definition);

  /**
   * Removes a field definition object from the deleted list.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   A field definition object.
   *
   * @return $this
   */
  public function removeFieldDefinition(FieldDefinitionInterface $field_definition);

  /**
   * Removes a field storage definition object from the deleted list.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   A field storage definition object.
   *
   * @return $this
   */
  public function removeFieldStorageDefinition(FieldStorageDefinitionInterface $field_storage_definition);

}
