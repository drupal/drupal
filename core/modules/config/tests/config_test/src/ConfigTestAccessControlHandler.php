<?php

/**
 * @file
 * Contains \Drupal\config_test\ConfigTestAccessControlHandler.
 */

namespace Drupal\config_test;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the access control handler for the config_test entity type.
 *
 * @see \Drupal\config_test\Entity\ConfigTest
 */
class ConfigTestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowed();
  }

}
