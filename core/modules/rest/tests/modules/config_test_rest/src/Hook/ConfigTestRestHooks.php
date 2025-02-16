<?php

declare(strict_types=1);

namespace Drupal\config_test_rest\Hook;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for config_test_rest.
 */
class ConfigTestRestHooks {

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    // Undo part of what config_test_entity_type_alter() did: remove this
    // config_test_no_status entity type, because it uses the same entity class
    // as the config_test entity type, which makes REST deserialization
    // impossible.
    unset($entity_types['config_test_no_status']);
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('config_test_access')]
  public function configTestAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResultInterface {
    // Add permission, so that EntityResourceTestBase's scenarios can test
    // access being denied. By default, all access is always allowed for the
    // config_test config entity.
    $access_result = AccessResult::forbiddenIf(!$account->hasPermission('view config_test'))->cachePerPermissions();
    if (!$access_result->isAllowed() && $access_result instanceof AccessResultReasonInterface) {
      $access_result->setReason("The 'view config_test' permission is required.");
    }
    return $access_result;
  }

}
