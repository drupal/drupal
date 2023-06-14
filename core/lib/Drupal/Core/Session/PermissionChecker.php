<?php

namespace Drupal\Core\Session;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Checks permissions for an account.
 */
class PermissionChecker implements PermissionCheckerInterface {

  /**
   * Constructs a PermissionChecker object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public function hasPermission(string $permission, AccountInterface $account): bool {
    // User #1 has all privileges.
    if ((int) $account->id() === 1) {
      return TRUE;
    }

    return $this->entityTypeManager->getStorage('user_role')->isPermissionInRoles($permission, $account->getRoles());
  }

}
