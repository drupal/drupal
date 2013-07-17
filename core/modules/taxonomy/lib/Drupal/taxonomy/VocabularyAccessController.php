<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyAccessController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the vocabulary entity.
 *
 * @see \Drupal\taxonomy\Plugin\Core\Entity\Vocabulary.
 */
class VocabularyAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    return user_access('administer taxonomy', $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return user_access('administer taxonomy', $account);
  }

}
