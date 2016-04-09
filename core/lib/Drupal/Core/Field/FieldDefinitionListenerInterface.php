<?php

namespace Drupal\Core\Field;

/**
 * Defines an interface for reacting to field creation, deletion, and updates.
 */
interface FieldDefinitionListenerInterface {

  /**
   * Reacts to the creation of a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition created.
   */
  public function onFieldDefinitionCreate(FieldDefinitionInterface $field_definition);

  /**
   * Reacts to the update of a field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition being updated.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $original
   *   The original field definition; i.e., the definition before the update.
   */
  public function onFieldDefinitionUpdate(FieldDefinitionInterface $field_definition, FieldDefinitionInterface $original);

  /**
   * Reacts to the deletion of a field.
   *
   * Stored values should not be wiped at once, but marked as 'deleted' so that
   * they can go through a proper purge process later on.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition being deleted.
   */
  public function onFieldDefinitionDelete(FieldDefinitionInterface $field_definition);

}
