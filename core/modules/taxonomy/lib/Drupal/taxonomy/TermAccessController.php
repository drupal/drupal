<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermAccessController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the taxonomy term entity.
 *
 * @see \Drupal\taxonomy\Plugin\Core\Entity\Term
 */
class TermAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return user_access('access content', $account);
        break;

      case 'update':
        return user_access("edit terms in {$entity->bundle()}", $account) || user_access('administer taxonomy', $account);
        break;

      case 'delete':
        return user_access("delete terms in {$entity->bundle()}", $account) || user_access('administer taxonomy', $account);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return user_access('administer taxonomy', $account);
  }

}
