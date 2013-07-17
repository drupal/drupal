<?php

/**
 * @file
 * Contains \Drupal\taxonomy\NodeTypeAccessController.
 */

namespace Drupal\node;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the node type entity.
 *
 * @see \Drupal\node\Plugin\Core\Entity\NodeType.
 */
class NodeTypeAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    if ($operation == 'delete' && $entity->isLocked()) {
      return FALSE;
    }
    return user_access('administer content types', $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return user_access('administer content types', $account);
  }

}
