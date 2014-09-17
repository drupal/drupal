<?php

/**
 * @file
 * Contains \Drupal\quickedit\Access\EditEntityFieldAccessCheckInterface.
 */

namespace Drupal\quickedit\Access;

use Drupal\Core\Entity\EntityInterface;

/**
 * Access check for editing entity fields.
 */
interface EditEntityFieldAccessCheckInterface {

  /**
   * Checks access to edit the requested field of the requested entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The field name.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name);

}
