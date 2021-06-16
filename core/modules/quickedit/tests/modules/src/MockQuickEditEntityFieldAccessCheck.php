<?php

namespace Drupal\quickedit_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\quickedit\Access\QuickEditEntityFieldAccessCheckInterface;

/**
 * Access check for in-place editing entity fields.
 */
class MockQuickEditEntityFieldAccessCheck implements QuickEditEntityFieldAccessCheckInterface {

  /**
   * {@inheritdoc}
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name) {
    return TRUE;
  }

}
