<?php

/**
 * @file
 * Contains \Drupal\field\FieldInstanceConfigInterface.
 */

namespace Drupal\field;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Provides an interface defining a field instance entity.
 */
interface FieldInstanceConfigInterface extends ConfigEntityInterface, FieldDefinitionInterface {

  /**
   * Gets the deleted flag of the field instance.
   *
   * @return bool
   *   Returns TRUE if the instance is deleted.
   */
  public function isDeleted();

}
