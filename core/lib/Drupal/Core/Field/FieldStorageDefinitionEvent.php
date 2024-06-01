<?php

namespace Drupal\Core\Field;

use Drupal\Component\EventDispatcher\Event;

/**
 * Defines a base class for all field storage definition events.
 */
class FieldStorageDefinitionEvent extends Event {

  /**
   * The field storage definition.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $fieldStorageDefinition;

  /**
   * The original field storage definition.
   *
   * @var \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  protected $original;

  /**
   * Constructs a new FieldStorageDefinitionEvent.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   (optional) The original field storage definition. This should be passed
   *   only when updating the storage definition.
   */
  public function __construct(FieldStorageDefinitionInterface $field_storage_definition, ?FieldStorageDefinitionInterface $original = NULL) {
    $this->fieldStorageDefinition = $field_storage_definition;
    $this->original = $original;
  }

  /**
   * The field storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  public function getFieldStorageDefinition() {
    return $this->fieldStorageDefinition;
  }

  /**
   * The original field storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   */
  public function getOriginal() {
    return $this->original;
  }

}
