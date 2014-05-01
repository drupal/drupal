<?php

/**
 * @file
 * Contains \Drupal\path\Plugin\Field\FieldType\PathFieldItemList.
 */

namespace Drupal\path\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Represents a configurable entity path field.
 */
class PathFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultAccess($operation = 'view', AccountInterface $account = NULL) {
    if ($operation == 'view') {
      return TRUE;
    }
    return $account->hasPermission('create url aliases') || $account->hasPermission('administer url aliases');
  }

}
