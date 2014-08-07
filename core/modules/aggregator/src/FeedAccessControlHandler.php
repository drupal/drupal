<?php

/**
 * @file
 * Contains \Drupal\aggregator\FeedAccessControlHandler.
 */

namespace Drupal\aggregator;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access control handler for the feed entity.
 *
 * @see \Drupal\aggregator\Entity\Feed
 */
class FeedAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return $account->hasPermission('access news feeds');
        break;

      default:
        return $account->hasPermission('administer news feeds');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return $account->hasPermission('administer news feeds');
  }

}
