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
        return user_access('view test entity translations', $account);
      }
      return user_access('view test entity', $account);
    }
    elseif (in_array($operation, array('update', 'delete'))) {
      return user_access('administer entity_test content', $account);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return user_access('administer entity_test content', $account);
  }

}
