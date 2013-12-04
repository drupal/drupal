<?php

/**
 * @file
 * Contains \Drupal\edit_test\MockEditEntityFieldAccessCheck.
 */

namespace Drupal\edit_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\edit\Access\EditEntityFieldAccessCheckInterface;

/**
 * Access check for editing entity fields.
 */
class MockEditEntityFieldAccessCheck implements EditEntityFieldAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name) {
    return TRUE;
  }

}
