<?php

namespace Drupal\quickedit_test;

use Drupal\Core\Access\AccessResult;
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
    switch (\Drupal::state()->get('quickedit_test_field_access')) {
      case 'allowed':
        return AccessResult::allowed();

      case 'neutral':
        return AccessResult::neutral();

      case 'forbidden':
        return AccessResult::forbidden();

      default:
        throw new \OutOfRangeException("The state for the 'quickedit_test_field_access' key must be either 'allowed', 'neutral' or 'forbidden'.");
    }
  }

}
