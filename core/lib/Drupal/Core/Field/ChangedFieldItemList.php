<?php

/**
 * @file
 * Contains \Drupal\Core\Field\ChangedFieldItemList.
 */

namespace Drupal\Core\Field;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a item list class for changed fields.
 */
class ChangedFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    // It is not possible to edit the changed field.
    return AccessResult::allowedIf($operation !== 'edit');
  }
}
