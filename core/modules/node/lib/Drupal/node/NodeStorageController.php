<?php

/**
 * @file
 * Definition of Drupal\node\NodeStorageController.
 */

namespace Drupal\node;

use Drupal\Core\Entity\DatabaseStorageController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for nodes.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for node entities.
 */
class NodeStorageController extends DatabaseStorageController {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    $node = parent::create($values);

    // Set the created time to now.
    if (empty($node->created)) {
      $node->created = REQUEST_TIME;
    }

    return $node;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::attachLoad().
   */
  protected function attachLoad(&$nodes, $load_revision = FALSE) {
    // Create an array of nodes for each content type and pass this to the
    // object type specific callback.
    $typed_nodes = array();
    foreach ($nodes as $id => $entity) {
      $typed_nodes[$entity->type][$id] = $entity;
    }

    // Call object type specific callbacks on each typed array of nodes.
    foreach ($typed_nodes as $node_type => $nodes_of_type) {
      // Retrieve the node type 'base' hook implementation based on a Node in
      // the type-specific stack.
      if ($function = node_hook($node_type, 'load')) {
        $function($nodes_of_type);
      }
    }
    // Besides the list of nodes, pass one additional argument to
    // hook_node_load(), containing a list of node types that were loaded.
    $argument = array_keys($typed_nodes);
    $this->hookLoadArguments = array($argument);
    parent::attachLoad($nodes, $load_revision);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildQuery().
   */
  protected function buildQuery($ids, $revision_id = FALSE) {
    // Ensure that uid is taken from the {node} table,
    // alias timestamp to revision_timestamp and add revision_uid.
    $query = parent::buildQuery($ids, $revision_id);
    $fields =& $query->getFields();
    unset($fields['timestamp']);
    $query->addField('revision', 'timestamp', 'revision_timestamp');
    $fields['uid']['table'] = 'base';
    $query->addField('revision', 'uid', 'revision_uid');
    return $query;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::invokeHook().
   */
  protected function invokeHook($hook, EntityInterface $node) {
    if ($hook == 'insert' || $hook == 'update') {
      node_invoke($node, $hook);
    }
    else if ($hook == 'predelete') {
      // 'delete' is triggered in 'predelete' is here to preserve hook ordering
      // from Drupal 7.
      node_invoke($node, 'delete');
    }

    parent::invokeHook($hook, $node);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   */
  protected function preSave(EntityInterface $node) {
    // Before saving the node, set changed and revision times.
    $node->changed = REQUEST_TIME;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSaveRevision().
   */
  protected function preSaveRevision(array &$record, EntityInterface $entity) {
    if ($entity->isNewRevision()) {
      // When inserting either a new node or a new node revision, $node->log
      // must be set because {node_revision}.log is a text column and therefore
      // cannot have a default value. However, it might not be set at this
      // point (for example, if the user submitting a node form does not have
      // permission to create revisions), so we ensure that it is at least an
      // empty string in that case.
      // @todo: Make the {node_revision}.log column nullable so that we can
      // remove this check.
      if (!isset($record['log'])) {
        $record['log'] = '';
      }
    }
    elseif (!isset($record['log']) || $record['log'] === '') {
      // If we are updating an existing node without adding a new revision, we
      // need to make sure $node->log is unset whenever it is empty. As long as
      // $node->log is unset, drupal_write_record() will not attempt to update
      // the existing database column when re-saving the revision; therefore,
      // this code allows us to avoid clobbering an existing log entry with an
      // empty one.
      unset($record['log']);
    }

    if ($entity->isNewRevision()) {
      $record['timestamp'] = REQUEST_TIME;
      $record['uid'] = isset($record['revision_uid']) ? $record['revision_uid'] : $GLOBALS['user']->uid;
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  function postSave(EntityInterface $node, $update) {
    // Update the node access table for this node, but only if it is the
    // default revision. There's no need to delete existing records if the node
    // is new.
    if ($node->isDefaultRevision()) {
      node_access_acquire_grants($node, $update);
    }
  }
  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preDelete().
   */
  function preDelete($entities) {
    if (module_exists('search')) {
      foreach ($entities as $id => $entity) {
        search_reindex($entity->nid, 'node');
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($nodes) {
    // Delete values from other tables also referencing this node.
    $ids = array_keys($nodes);

    db_delete('history')
      ->condition('nid', $ids, 'IN')
      ->execute();
    db_delete('node_access')
      ->condition('nid', $ids, 'IN')
      ->execute();
  }
}
