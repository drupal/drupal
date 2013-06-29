<?php

/**
 * @file
 * Contains \Drupal\config_test\ConfigTestAccessController.
 */

namespace Drupal\config_test;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;

/**
 * Defines the access controller for the config_test entity type.
 */
class ConfigTestAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, $langcode = Language::LANGCODE_DEFAULT, AccountInterface $account = NULL) {
    return TRUE;
  }

}
