<?php

namespace Drupal\layout_builder\Access;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Accessible class to allow access for inline blocks in the Layout Builder.
 *
 * @internal
 *   Tagged services are internal.
 */
class LayoutPreviewAccessAllowed implements AccessibleInterface {

  /**
   * {@inheritdoc}
   */
  public function access($operation, ?AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($operation === 'view') {
      return $return_as_object ? AccessResult::allowed() : TRUE;
    }
    // The layout builder preview should only need 'view' access.
    return $return_as_object ? AccessResult::forbidden() : FALSE;
  }

}
