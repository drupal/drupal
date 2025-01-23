<?php

namespace Drupal\entity_test;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTestLabel;

/**
 * Defines the access control handler for the test entity type.
 *
 * @see \Drupal\entity_test\Entity\EntityTest
 * @see \Drupal\entity_test\Entity\EntityTestBaseFieldDisplay
 * @see \Drupal\entity_test\Entity\EntityTestCache
 * @see \Drupal\entity_test\Entity\EntityTestMul
 * @see \Drupal\entity_test\Entity\EntityTestMulRev
 * @see \Drupal\entity_test\Entity\EntityTestRev
 * @see \Drupal\entity_test\Entity\EntityTestWithBundle
 * @see \Drupal\entity_test\Entity\EntityTestStringId
 */
class EntityTestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * Allows to grant access to just the labels.
   *
   * @var bool
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */

    // Always forbid access to entities with the label 'forbid_access', used for
    // \Drupal\system\Tests\Entity\EntityAccessControlHandlerTest::testDefaultEntityAccess().
    if ($entity->label() == 'forbid_access') {
      return AccessResult::forbidden();
    }

    if ($operation === 'view label' && $entity instanceof EntityTestLabel) {
      // Viewing the label of the 'entity_test_label' entity type is allowed.
      return AccessResult::allowed();
    }
    elseif (in_array($operation, ['view', 'view label'])) {
      if (!$entity->isDefaultTranslation()) {
        if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished test entity translations');
        }
        else {
          return AccessResult::allowedIfHasPermission($account, 'view test entity translations');
        }
      }
      if ($entity instanceof EntityPublishedInterface && !$entity->isPublished()) {
        return AccessResult::neutral('Unpublished entity');
      }
      return AccessResult::allowedIfHasPermission($account, 'view test entity');
    }
    elseif (in_array($operation, ['update', 'delete'])) {
      $access = AccessResult::allowedIfHasPermission($account, 'administer entity_test content');
      if (!$access->isAllowed() && $operation === 'update' && $account->hasPermission('edit own entity_test content')) {
        $access = $access->orIf(AccessResult::allowedIf($entity->getOwnerId() === $account->id()))->cachePerUser()->addCacheableDependency($entity);
      }
      return $access;
    }

    // Access to revisions is based on labels, so access can vary by individual
    // revisions, since the 'name' field can vary by revision.
    $labels = explode(',', $entity->label());
    $labels = array_map('trim', $labels);
    if (in_array($operation, [
      'view all revisions',
      'view revision',
    ], TRUE)) {
      return AccessResult::allowedIf(in_array($operation, $labels, TRUE));
    }
    elseif ($operation === 'revert') {
      return AccessResult::allowedIf(in_array('revert', $labels, TRUE));
    }
    elseif ($operation === 'delete revision') {
      return AccessResult::allowedIf(in_array('delete revision', $labels, TRUE));
    }

    // No opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermissions($account, [
      'administer entity_test content',
      'administer entity_test_with_bundle content',
      'create ' . $entity_bundle . ' entity_test_with_bundle entities',
    ], 'OR');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCreateAccessCid(array $context, ?string $entity_bundle): string {
    $cid = parent::buildCreateAccessCid([], $entity_bundle);
    $cid .= isset($context['context_var1']) ? ":{$context['context_var1']}" : '';
    $cid .= isset($context['context_var2']) ? ":{$context['context_var2']}" : '';
    return $cid;
  }

}
