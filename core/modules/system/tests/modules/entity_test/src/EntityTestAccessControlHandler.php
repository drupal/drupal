<?php

/**
 * @file
 * Contains \Drupal\entity_test\EntityTestAccessControlHandler.
 */

namespace Drupal\entity_test;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the test entity type.
 *
 * @see \Drupal\entity_test\Entity\EntityTest
 * @see \Drupal\entity_test\Entity\EntityTestBaseFieldDisplay
 * @see \Drupal\entity_test\Entity\EntityTestCache
 * @see \Drupal\entity_test\Entity\EntityTestMul
 * @see \Drupal\entity_test\Entity\EntityTestMulRev
 * @see \Drupal\entity_test\Entity\EntityTestRev
 * @see \Drupal\entity_test\Entity\EntityTestStringId
 */
class EntityTestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    // Always forbid access to entities with the label 'forbid_access', used for
    // \Drupal\system\Tests\Entity\EntityAccessHControlandlerTest::testDefaultEntityAccess().
    if ($entity->label() == 'forbid_access') {
      return AccessResult::forbidden();
    }

    if ($operation === 'view') {
      if ($langcode != LanguageInterface::LANGCODE_DEFAULT) {
        return AccessResult::allowedIfHasPermission($account, 'view test entity translations');
      }
      return AccessResult::allowedIfHasPermission($account, 'view test entity');
    }
    elseif (in_array($operation, array('update', 'delete'))) {
      return AccessResult::allowedIfHasPermission($account, 'administer entity_test content');
    }

    // No opinion.
    return AccessResult::neutral();

  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'administer entity_test content');
  }

}
