<?php

/**
 * @file
 * Contains \Drupal\language\LanguageAccessControlHandler.
 */

namespace Drupal\language;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the language entity type.
 *
 * @see \Drupal\language\Entity\Language
 */
class LanguageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'update':
      case 'delete':
        return !$entity->locked && parent::checkAccess($entity, $operation, $langcode, $account);
        break;
    }
    return FALSE;
  }

}
