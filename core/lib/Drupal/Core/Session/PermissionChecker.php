<?php

namespace Drupal\Core\Session;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Checks permissions for an account.
 */
class PermissionChecker implements PermissionCheckerInterface {

  public function __construct(protected EntityTypeManagerInterface|AccessPolicyProcessorInterface $processor) {
    if ($this->processor instanceof EntityTypeManagerInterface) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $processor argument is deprecated in drupal:10.3.0 and will be required in drupal:11.0.0. See https://www.drupal.org/node/3402107', E_USER_DEPRECATED);
      $this->processor = \Drupal::service('access_policy_processor');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission(string $permission, AccountInterface $account): bool {
    $item = $this->processor->processAccessPolicies($account)->getItem();
    return $item && $item->hasPermission($permission);
  }

}
