<?php

namespace Drupal\quickedit_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\quickedit\Access\EditEntityFieldAccessCheckInterface;

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
