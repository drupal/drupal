<?php

namespace Drupal\Core\Field;

use Drupal\Component\EventDispatcher\Event;

/**
 * Defines a base class for all field storage definition events.
 */
class FieldStorageDefinitionEvent extends Event {

  /**
   * Constructs a new FieldStorageDefinitionEvent.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $fieldStorageDefinition
   *   The field storage definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $original
   *   (optional) The original field storage definition. This should be passed
   *   only when updating the storage definition.
   */
  public function __construct(
    protected FieldStorageDefinitionInterface $fieldStorageDefinition,
    protected ?FieldStorageDefinitionInterface $original = NULL,
  ) {
  }

  /**
   * The field storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The field storage definition for the entity.
   */
  public function getFieldStorageDefinition() {
    return $this->fieldStorageDefinition;
  }

  /**
   * The original field storage definition.
   *
   * @return \Drupal\Core\Field\FieldStorageDefinitionInterface
   *   The field storage definition for the original entity.
   */
  public function getOriginal() {
    return $this->original;
  }

}
