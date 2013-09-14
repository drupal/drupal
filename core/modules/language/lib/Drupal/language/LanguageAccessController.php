<?php

/**
 * @file
 * Contains \Drupal\language\LanguageAccessController.
 */

namespace Drupal\language;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

class LanguageAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    switch ($operation) {
      case 'update':
      case 'delete':
        return !$entity->locked && user_access('administer languages');
        break;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer languages');
  }

}
