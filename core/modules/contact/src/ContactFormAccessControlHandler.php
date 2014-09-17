<?php

/**
 * @file
 * Contains \Drupal\contact\ContactFormAccessControlHandler.
 */

namespace Drupal\contact;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the contact form entity type.
 *
 * @see \Drupal\contact\Entity\ContactForm.
 */
class ContactFormAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'view') {
      // Do not allow access personal form via site-wide route.
      return AccessResult::allowedIf($account->hasPermission('access site-wide contact form') && $entity->id() !== 'personal')->cachePerRole();
    }
    elseif ($operation == 'delete' || $operation == 'update') {
      // Do not allow the 'personal' form to be deleted, as it's used for
      // the personal contact form.
      return AccessResult::allowedIf($account->hasPermission('administer contact forms') && $entity->id() !== 'personal')->cachePerRole();
    }

    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
