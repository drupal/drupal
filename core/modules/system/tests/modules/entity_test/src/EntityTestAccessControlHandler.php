<?php

namespace Drupal\entity_test;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
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
    // \Drupal\system\Tests\Entity\EntityAccessHControlandlerTest::testDefaultEntityAccess().
    if ($entity->label() == 'forbid_access') {
      return AccessResult::forbidden();
    }

    if ($operation === 'view label' && $entity instanceof EntityTestLabel) {
      // Viewing the label of the 'entity_test_label' entity type is allowed.
      return AccessResult::allowed();
    }
    elseif (in_array($operation, ['view', 'view label'])) {
      if (!$entity->isDefaultTranslation()) {
        return AccessResult::allowedIfHasPermission($account, 'view test entity translations');
      }
      return AccessResult::allowedIfHasPermission($account, 'view test entity');
    }
    elseif (in_array($operation, ['update', 'delete'])) {
      return AccessResult::allowedIfHasPermission($account, 'administer entity_test content');
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

}
