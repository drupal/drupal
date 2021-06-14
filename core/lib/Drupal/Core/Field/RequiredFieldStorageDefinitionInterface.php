<?php

namespace Drupal\Core\Field;

/**
 * Defines an interface for required field storage definitions.
 */
interface RequiredFieldStorageDefinitionInterface {

  /**
   * Returns whether the field storage is required.
   *
   * If a field storage is required, NOT NULL constraints will be added
   * automatically for the required properties of a field type.
   *
   * @return bool
   *   TRUE if the field storage is required, FALSE otherwise.
   */
  public function isStorageRequired();

}
