<?php

/**
 * @file
 * Contains \Drupal\node\NodeStorage.
 */

namespace Drupal\node;

use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

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
      ->fields(array('langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED))
      ->condition('langcode', $language->id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();

    // Marking the respective fields as NOT NULL makes the indexes more
    // performant.
    $schema['node_field_data']['fields']['changed']['not null'] = TRUE;
    $schema['node_field_data']['fields']['created']['not null'] = TRUE;
    $schema['node_field_data']['fields']['default_langcode']['not null'] = TRUE;
    $schema['node_field_data']['fields']['promote']['not null'] = TRUE;
    $schema['node_field_data']['fields']['status']['not null'] = TRUE;
    $schema['node_field_data']['fields']['sticky']['not null'] = TRUE;
    $schema['node_field_data']['fields']['title']['not null'] = TRUE;
    $schema['node_field_revision']['fields']['default_langcode']['not null'] = TRUE;

    // @todo Revisit index definitions in https://drupal.org/node/2015277.
    $schema['node_revision']['indexes'] += array(
      'node__langcode' => array('langcode'),
    );
    $schema['node_revision']['foreign keys'] += array(
      'node__revision_author' => array(
        'table' => 'users',
        'columns' => array('revision_uid' => 'uid'),
      ),
    );

    $schema['node_field_data']['indexes'] += array(
      'node__changed' => array('changed'),
      'node__created' => array('created'),
      'node__default_langcode' => array('default_langcode'),
      'node__langcode' => array('langcode'),
      'node__frontpage' => array('promote', 'status', 'sticky', 'created'),
      'node__status_type' => array('status', 'type', 'nid'),
      'node__title_type' => array('title', array('type', 4)),
    );

    $schema['node_field_revision']['indexes'] += array(
      'node__default_langcode' => array('default_langcode'),
      'node__langcode' => array('langcode'),
    );

    return $schema;
  }

}
