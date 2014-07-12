<?php

/**
 * @file
 * Contains Drupal\entity_test\EntityTestAccessController.
 */

namespace Drupal\entity_test;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access controller for the test entity type.
 */
class EntityTestAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation === 'view') {
      if ($langcode != LanguageInterface::LANGCODE_DEFAULT) {
        return $account->hasPermission('view test entity translations');
      }
      return $account->hasPermission('view test entity');
    }
    elseif (in_array($operation, array('update', 'delete'))) {
      return $account->hasPermission('administer entity_test content');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer entity_test content');
  }

}
