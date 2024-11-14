<?php

declare(strict_types=1);

namespace Drupal\user_access_test\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for user_access_test.
 */
class UserAccessTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_access() for entity type "user".
   */
  #[Hook('user_access')]
  public function userAccess(User $entity, $operation, $account) {
    if ($entity->getAccountName() == "no_edit" && $operation == "update") {
      // Deny edit access.
      return AccessResult::forbidden();
    }
    if ($entity->getAccountName() == "no_delete" && $operation == "delete") {
      // Deny delete access.
      return AccessResult::forbidden();
    }
    // Account with role sub-admin can manage users with no roles.
    if (count($entity->getRoles()) == 1) {
      return AccessResult::allowedIfHasPermission($account, 'sub-admin');
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_create_access().
   */
  #[Hook('entity_create_access')]
  public function entityCreateAccess(AccountInterface $account, array $context, $entity_bundle) {
    if ($context['entity_type_id'] != 'user') {
      return AccessResult::neutral();
    }
    // Account with role sub-admin can create users.
    return AccessResult::allowedIfHasPermission($account, 'sub-admin');
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL) {
    // Account with role sub-admin can view the status, init and mail fields for
    // user with no roles.
    if ($field_definition->getTargetEntityTypeId() == 'user' && $operation === 'view' && in_array($field_definition->getName(), ['status', 'init', 'mail'])) {
      if ($items == NULL || count($items->getEntity()->getRoles()) == 1) {
        return AccessResult::allowedIfHasPermission($account, 'sub-admin');
      }
    }
    if (\Drupal::state()->get('user_access_test_forbid_mail_edit', FALSE)) {
      if ($operation === 'edit' && $items && $items->getEntity()->getEntityTypeId() === 'user' && $field_definition->getName() === 'mail') {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

}
