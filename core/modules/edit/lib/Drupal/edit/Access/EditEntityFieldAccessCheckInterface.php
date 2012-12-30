<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityFieldAccessCheckInterface.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entity fields.
 */
interface EditEntityFieldAccessCheckInterface {

  /**
   * Checks access to edit the requested field of the requested entity.
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name);

}
