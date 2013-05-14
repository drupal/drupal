<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyAccessController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Plugin\Core\Entity\User;

/**
 * Defines an access controller for the vocabulary entity.
 *
 * @see \Drupal\taxonomy\Plugin\Core\Entity\Vocabulary.
 */
class VocabularyAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, User $account) {
    return user_access('administer taxonomy', $account);
  }

}
