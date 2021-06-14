<?php

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides an interface defining a field entity.
 */
interface FieldConfigInterface extends ConfigEntityInterface, FieldDefinitionInterface {

  /**
   * Gets the deleted flag of the field.
   *
   * @return bool
   *   Returns TRUE if the field is deleted.
   */
  public function isDeleted();

}
