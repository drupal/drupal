<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Moderation State entity.
 *
 * @see \Drupal\workbench_moderation\Entity\ModerationState.
 */
class ModerationStateAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $admin_access = parent::checkAccess($entity, $operation, $account);

    // Allow view with other permission.
    if ($operation === 'view') {
      return AccessResult::allowedIfHasPermission($account, 'view moderation states')->orIf($admin_access);
    }

    return $admin_access;
  }

}
