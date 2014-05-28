<?php

/**
 * @file
 * Definition of Drupal\node\NodeStorageController.
 */

namespace Drupal\node;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\Language;

/**
 * Defines the controller class for nodes.
 *
 * This extends the base storage class, adding required special handling for
 * node entities.
 */
class NodeStorage extends ContentEntityDatabaseStorage implements NodeStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(NodeInterface $node) {
    return $this->database->query(
      'SELECT vid FROM {node_revision} WHERE nid=:nid ORDER BY vid',
      array(':nid' => $node->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {node_field_revision} WHERE uid = :uid ORDER BY vid',
      array(':uid' => $account->id())
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function updateType($old_type, $new_type) {
    return $this->database->update('node')
      ->fields(array('type' => $new_type))
      ->condition('type', $old_type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage($language) {
    return $this->database->update('node_revision')
      ->fields(array('langcode' => Language::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->id)
      ->execute();
  }
}
