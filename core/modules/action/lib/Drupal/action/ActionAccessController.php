<?php

/**
 * @file
 * Contains \Drupal\action\ActionAccessController.
 */

namespace Drupal\action;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Session\AccountInterface;

class ActionAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    return user_access('administer actions', $account);
  }

}
