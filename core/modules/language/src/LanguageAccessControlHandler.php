<?php

namespace Drupal\language;

use Drupal\Core\Access\AccessResult;
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
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'update':
        /* @var \Drupal\Core\Language\LanguageInterface $entity */
        return AccessResult::allowedIf(!$entity->isLocked())->addCacheableDependency($entity)
          ->andIf(parent::checkAccess($entity, $operation, $account));

      case 'delete':
        /* @var \Drupal\Core\Language\LanguageInterface $entity */
        return AccessResult::allowedIf(!$entity->isLocked())->addCacheableDependency($entity)
          ->andIf(AccessResult::allowedIf(!$entity->isDefault())->addCacheableDependency($entity))
          ->andIf(parent::checkAccess($entity, $operation, $account));

      default:
        // No opinion.
        return AccessResult::neutral();
    }
  }

}
