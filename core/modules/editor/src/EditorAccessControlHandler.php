<?php

namespace Drupal\editor;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the text editor entity type.
 *
 * @see \Drupal\editor\Entity\Editor
 */
class EditorAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $editor, $operation, AccountInterface $account) {
    /** @var \Drupal\editor\EditorInterface $editor */
    return $editor->getFilterFormat()->access($operation, $account, TRUE);
  }

}
