<?php

/**
 * @file
 * Contains \Drupal\edit\Access\EditEntityAccessCheckInterface.
 */

namespace Drupal\edit\Access;

use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entities.
 */
interface EditEntityAccessCheckInterface {

  /**
   * Checks access to edit the requested entity.
   */
  public function accessEditEntity(EntityInterface $entity);

}
