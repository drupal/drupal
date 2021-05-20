<?php

namespace Drupal\search;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the search page entity type.
 *
 * @see \Drupal\search\Entity\SearchPage
 */
class SearchPageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\search\SearchPageInterface $entity */
    if (in_array($operation, ['delete', 'disable'])) {
      if ($entity->isDefaultSearch()) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $account)->addCacheableDependency($entity);
      }
    }
    if ($operation == 'view') {
      if (!$entity->status()) {
        return AccessResult::forbidden()->addCacheableDependency($entity);
      }
      $plugin = $entity->getPlugin();
      if ($plugin instanceof AccessibleInterface) {
        return $plugin->access($operation, $account, TRUE)->addCacheableDependency($entity);
      }
      return AccessResult::allowed()->addCacheableDependency($entity);
    }
    return parent::checkAccess($entity, $operation, $account);
  }

}
