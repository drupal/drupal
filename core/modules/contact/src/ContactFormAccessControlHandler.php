<?php

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
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view') {
      // Do not allow access personal form via site-wide route.
      return AccessResult::allowedIfHasPermission($account, 'access site-wide contact form')->andIf(AccessResult::allowedIf($entity->id() !== 'personal'));
    }
    elseif ($operation == 'delete' || $operation == 'update') {
      // Do not allow the 'personal' form to be deleted, as it's used for
      // the personal contact form.
      return AccessResult::allowedIfHasPermission($account, 'administer contact forms')->andIf(AccessResult::allowedIf($entity->id() !== 'personal'));
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
