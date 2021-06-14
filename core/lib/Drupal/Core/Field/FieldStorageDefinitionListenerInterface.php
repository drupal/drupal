<?php

namespace Drupal\Core\Field;

/**
 * Defines an interface for reacting to field storage definition creation, deletion, and updates.
 */
interface FieldStorageDefinitionListenerInterface {

  /**
   * Reacts to the creation of a field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The definition being created.
   */
  public function onFieldStorageDefinitionCreate(FieldStorageDefinitionInterface $storage_definition);

  /**
   * Reacts to the update of a field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field being updated.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   The original storage definition; i.e., the definition before the update.
   *
   * @throws \Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException
   *   Thrown when the update to the field is forbidden.
   */
  public function onFieldStorageDefinitionUpdate(FieldStorageDefinitionInterface $storage_definition, FieldStorageDefinitionInterface $original);

  /**
   * Reacts to the deletion of a field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_definition
   *   The field being deleted.
   */
  public function onFieldStorageDefinitionDelete(FieldStorageDefinitionInterface $storage_definition);

}
