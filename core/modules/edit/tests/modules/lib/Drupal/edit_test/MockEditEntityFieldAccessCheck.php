<?php

/**
 * @file
 * Contains \Drupal\edit_test\MockEditEntityFieldAccessCheck.
 *
 * @todo We may want to get rid of this once http://drupal.org/node/1862750
 * is done.
 */

namespace Drupal\edit_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\edit\Access\EditEntityFieldAccessCheckInterface;

/**
 * Access check for editing entity fields.
 */
class MockEditEntityFieldAccessCheck implements EditEntityFieldAccessCheckInterface {

  /**
   * Implements EntityFieldAccessCheckInterface::accessEditEntityField().
   */
  public function accessEditEntityField(EntityInterface $entity, $field_name) {
    return TRUE;
  }

}
