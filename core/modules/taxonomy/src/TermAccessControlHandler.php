<?php

namespace Drupal\taxonomy;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the taxonomy term entity type.
 *
 * @see \Drupal\taxonomy\Entity\Term
 */
class TermAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission('administer taxonomy')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    switch ($operation) {
      case 'view':
        $access_result = AccessResult::allowedIf($account->hasPermission('access content') && $entity->isPublished())
          ->cachePerPermissions()
          ->addCacheableDependency($entity);
        if (!$access_result->isAllowed()) {
          $access_result->setReason("The 'access content' permission is required and the taxonomy term must be published.");
        }
        return $access_result;

      case 'update':
        if ($account->hasPermission("edit terms in {$entity->bundle()}")) {
          return AccessResult::allowed()->cachePerPermissions();
        }

        return AccessResult::neutral()->setReason("The following permissions are required: 'edit terms in {$entity->bundle()}' OR 'administer taxonomy'.");

      case 'delete':
        if ($account->hasPermission("delete terms in {$entity->bundle()}")) {
          return AccessResult::allowed()->cachePerPermissions();
        }

        return AccessResult::neutral()->setReason("The following permissions are required: 'delete terms in {$entity->bundle()}' OR 'administer taxonomy'.");

      case 'view revision':
      case 'view all revisions':
        if ($account->hasPermission("view term revisions in {$entity->bundle()}") || $account->hasPermission("view all taxonomy revisions")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->setReason("The following permissions are required: 'view revisions in {$entity->bundle()}' OR 'view all taxonomy revisions'.");

      case 'revert':
        if (($account->hasPermission("revert term revisions in {$entity->bundle()}") && $account->hasPermission("edit terms in {$entity->bundle()}")) || $account->hasPermission("revert all taxonomy revisions")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->setReason("The following permissions are required: 'revert term revisions in {$entity->bundle()}' OR 'revert all taxonomy revisions'.");

      case 'delete revision':
        if (($account->hasPermission("delete term revisions in {$entity->bundle()}") && $account->hasPermission("delete terms in {$entity->bundle()}")) || $account->hasPermission("delete all taxonomy revisions")) {
          return AccessResult::allowed()->cachePerPermissions();
        }
        return AccessResult::neutral()->setReason("The following permissions are required: 'delete term revisions in {$entity->bundle()}' OR 'delete all taxonomy revisions'.");

      default:
        // No opinion.
        return AccessResult::neutral()->cachePerPermissions();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, ["create terms in $entity_bundle", 'administer taxonomy'], 'OR');
  }

}
