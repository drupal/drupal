<?php

/**
 * @file
 * Contains \Drupal\contact\CategoryAccessController.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines an access controller for the contact category entity.
 *
 * @see \Drupal\contact\Plugin\Core\Entity\Category.
 */
class CategoryAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, User $account) {
    if ($operation == 'delete' || $operation == 'update') {
      // Do not allow delete 'personal' category used for personal contact form.
      return user_access('administer contact forms', $account) && $entity->id() !== 'personal';
    }
    else {
      return user_access('administer contact forms', $account);
    }
  }

}
