<?php

namespace Drupal\Core\Session;

/**
 * Checks permissions for an account.
 */
class PermissionChecker implements PermissionCheckerInterface {

  public function __construct(protected AccessPolicyProcessorInterface $processor) {
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission(string $permission, AccountInterface $account): bool {
    $item = $this->processor->processAccessPolicies($account)->getItem();
    return $item && $item->hasPermission($permission);
  }

}
