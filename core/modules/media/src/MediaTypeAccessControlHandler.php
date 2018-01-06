<?php

namespace Drupal\media;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the "Media Type" entity type.
 *
 * @see \Drupal\media\Entity\MediaType
 */
class MediaTypeAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected $viewLabelOperation = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view label') {
      return AccessResult::allowedIfHasPermission($account, 'view media');
    }
    else {
      return parent::checkAccess($entity, $operation, $account);
    }
  }

}
