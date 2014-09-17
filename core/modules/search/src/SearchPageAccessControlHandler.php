<?php

/**
 * @file
 * Contains \Drupal\search\SearchPageAccessControlHandler.
 */

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
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    /** @var $entity \Drupal\search\SearchPageInterface */
    if (in_array($operation, array('delete', 'disable'))) {
      if ($entity->isDefaultSearch()) {
        return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
      }
      else {
        return parent::checkAccess($entity, $operation, $langcode, $account)->cacheUntilEntityChanges($entity);
      }
    }
    if ($operation == 'view') {
      if (!$entity->status()) {
        return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
      }
      $plugin = $entity->getPlugin();
      if ($plugin instanceof AccessibleInterface) {
        return $plugin->access($operation, $account, TRUE)->cacheUntilEntityChanges($entity);
      }
      return AccessResult::allowed()->cacheUntilEntityChanges($entity);
    }
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
