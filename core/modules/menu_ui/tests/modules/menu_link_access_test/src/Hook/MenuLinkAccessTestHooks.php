<?php

declare(strict_types=1);

namespace Drupal\menu_link_access_test\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for menu_link_access_test.
 */
class MenuLinkAccessTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('menu_link_content_access')]
  public function entityTestAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    if (in_array($operation, ['update', 'delete'])) {
      return AccessResult::forbidden();
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_ENTITY_TYPE_create_access().
   */
  #[Hook('menu_link_content_create_access')]
  public function entityTestCreateAccess(AccountInterface $account, $context, $entity_bundle): AccessResultInterface {
    return AccessResult::forbidden();
  }

}
