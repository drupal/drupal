<?php

declare(strict_types=1);

namespace Drupal\workspace_access_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for workspace_access_test.
 */
class WorkspaceAccessTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_access() for the 'workspace' entity type.
   */
  #[Hook('workspace_access')]
  public function workspaceAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    return \Drupal::state()->get("workspace_access_test.result.{$operation}", AccessResult::neutral());
  }

}
