<?php

/**
 * @file
 * Contains \Drupal\contact\ContactMessageAccessControlHandler.
 */

namespace Drupal\contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the message form entity type.
 *
 * @see \Drupal\contact\Entity\Message.
 */
class ContactMessageAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'access site-wide contact form');
  }
}
