<?php

/**
 * @file
 * Contains \Drupal\Core\Field\FieldStorageDefinitionEvent.
 */

namespace Drupal\Core\Field;

/**
 * Contains all events thrown while handling field storage definitions.
 */
final class FieldStorageDefinitionEvents {

  /**
   * Event name for field storage definition creation.
   *
   * @var string
   */
  const CREATE = 'field_storage.definition.create';

  /**
   * Event name for field storage definition update.
   *
   * @var string
   */
  const UPDATE = 'field_storage.definition.update';

  /**
   * Event name for field storage definition deletion.
   *
   * @var string
   */
  const DELETE = 'field_storage.definition.delete';

}
