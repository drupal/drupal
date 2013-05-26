<?php

/**
 * @file
 * Definition of Drupal\node\NodeStorageController.
 */

namespace Drupal\node;

use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal\Core\Entity\EntityInterface;

/**
 * Controller class for nodes.
 *
 * This extends the Drupal\Core\Entity\DatabaseStorageController class, adding
 * required special handling for node entities.
 */
class NodeStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    // @todo Handle this through property defaults.
    if (empty($values['created'])) {
      $values['created'] = REQUEST_TIME;
    }
    return parent::create($values)->getBCEntity();
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageControllerNG::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    $nodes = $this->mapFromStorageRecords($queried_entities, $load_revision);

    // Create an array of nodes for each content type and pass this to the
    // object type specific callback. To preserve backward-compatibility we
    // pass on BC decorators to node-specific hooks, while we pass on the
    // regular entity objects else.
    $typed_nodes = array();
    foreach ($nodes as $id => $node) {
      $queried_entities[$id] = $node->getBCEntity();
      $typed_nodes[$node->bundle()][$id] = $queried_entities[$id];
    }

    if ($load_revision) {
      field_attach_load_revision($this->entityType, $queried_entities);
    }
    else {
      field_attach_load($this->entityType, $queried_entities);
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

    // Call hook_entity_load().
    foreach (module_implements('entity_load') as $module) {
      $function = $module . '_entity_load';
      $function($queried_entities, $this->entityType);
    }
    // Call hook_TYPE_load(). The first argument for hook_TYPE_load() are
    // always the queried entities, followed by additional arguments set in
    // $this->hookLoadArguments.
    $args = array_merge(array($queried_entities), $this->hookLoadArguments);
    foreach (module_implements($this->entityType . '_load') as $module) {
      call_user_func_array($module . '_' . $this->entityType . '_load', $args);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::invokeHook().
   */
  protected function invokeHook($hook, EntityInterface $node) {
    $node = $node->getBCEntity();

    if ($hook == 'insert' || $hook == 'update') {
      node_invoke($node, $hook);
    }
    else if ($hook == 'predelete') {
      // 'delete' is triggered in 'predelete' is here to preserve hook ordering
      // from Drupal 7.
      node_invoke($node, 'delete');
    }

    // Inline parent::invokeHook() to pass on BC-entities to node-specific
    // hooks.

    $function = 'field_attach_' . $hook;
    // @todo: field_attach_delete_revision() is named the wrong way round,
    // consider renaming it.
    if ($function == 'field_attach_revision_delete') {
      $function = 'field_attach_delete_revision';
    }
    if (!empty($this->entityInfo['fieldable']) && function_exists($function)) {
      $function($node);
    }

    // Invoke the hook.
    module_invoke_all($this->entityType . '_' . $hook, $node);
    // Invoke the respective entity-level hook.
    module_invoke_all('entity_' . $hook, $node, $this->entityType);
  }

  /**
   * {@inheritdoc}
   */
  protected function mapToDataStorageRecord(EntityInterface $entity, $langcode) {
    // @todo Remove this once comment is a regular entity field.
    $record = parent::mapToDataStorageRecord($entity, $langcode);
    $record->comment = isset($record->comment) ? intval($record->comment) : 0;
    return $record;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSave().
   */
  protected function preSave(EntityInterface $node) {
    // Before saving the node, set changed and revision times.
    $node->changed->value = REQUEST_TIME;
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preSaveRevision().
   */
  protected function preSaveRevision(\stdClass $record, EntityInterface $entity) {
    if ($entity->isNewRevision()) {
      // When inserting either a new node or a new node revision, $node->log
      // must be set because {node_field_revision}.log is a text column and
      // therefore cannot have a default value. However, it might not be set at
      // this point (for example, if the user submitting a node form does not
      // have permission to create revisions), so we ensure that it is at least
      // an empty string in that case.
      // @todo Make the {node_field_revision}.log column nullable so that we
      //   can remove this check.
      if (!isset($record->log)) {
        $record->log = '';
      }
    }
    elseif (isset($entity->original) && (!isset($record->log) || $record->log === '')) {
      // If we are updating an existing node without adding a new revision, we
      // need to make sure $entity->log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->log = $entity->original->log;
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  public function postSave(EntityInterface $node, $update) {
    // Update the node access table for this node, but only if it is the
    // default revision. There's no need to delete existing records if the node
    // is new.
    if ($node->isDefaultRevision()) {
      node_access_acquire_grants($node->getBCEntity(), $update);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::preDelete().
   */
  public function preDelete($entities) {
    if (module_exists('search')) {
      foreach ($entities as $id => $entity) {
        search_reindex($entity->nid->value, 'node');
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($nodes) {
    // Delete values from other tables also referencing this node.
    $ids = array_keys($nodes);

    db_delete('node_access')
      ->condition('nid', $ids, 'IN')
      ->execute();
  }

  /**
   * Overrides \Drupal\Core\Entity\DataBaseStorageControllerNG::basePropertyDefinitions().
   */
  public function baseFieldDefinitions() {
    $properties['nid'] = array(
      'label' => t('Node ID'),
      'description' => t('The node ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The node UUID.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['vid'] = array(
      'label' => t('Revision ID'),
      'description' => t('The node revision ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['type'] = array(
      'label' => t('Type'),
      'description' => t('The node type.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['langcode'] = array(
      'label' => t('Language code'),
      'description' => t('The node language code.'),
      'type' => 'language_field',
    );
    $properties['title'] = array(
      'label' => t('Title'),
      'description' => t('The title of this node, always treated as non-markup plain text.'),
      'type' => 'string_field',
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the node author.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    $properties['status'] = array(
      'label' => t('Publishing status'),
      'description' => t('A boolean indicating whether the node is published.'),
      'type' => 'boolean_field',
    );
    $properties['created'] = array(
      'label' => t('Created'),
      'description' => t('The time that the node was created.'),
      'type' => 'integer_field',
    );
    $properties['changed'] = array(
      'label' => t('Changed'),
      'description' => t('The time that the node was last edited.'),
      'type' => 'integer_field',
    );
    $properties['comment'] = array(
      'label' => t('Comment'),
      'description' => t('Whether comments are allowed on this node: 0 = no, 1 = closed (read only), 2 = open (read/write).'),
      'type' => 'integer_field',
    );
    $properties['promote'] = array(
      'label' => t('Promote'),
      'description' => t('A boolean indicating whether the node should be displayed on the front page.'),
      'type' => 'boolean_field',
    );
    $properties['sticky'] = array(
      'label' => t('Sticky'),
      'description' => t('A boolean indicating whether the node should be displayed at the top of lists in which it appears.'),
      'type' => 'boolean_field',
    );
    $properties['tnid'] = array(
      'label' => t('Translation set ID'),
      'description' => t('The translation set id for this node, which equals the node id of the source post in each set.'),
      'type' => 'integer_field',
    );
    $properties['translate'] = array(
      'label' => t('Translate'),
      'description' => t('A boolean indicating whether this translation page needs to be updated.'),
      'type' => 'boolean_field',
    );
    $properties['revision_timestamp'] = array(
      'label' => t('Revision timestamp'),
      'description' => t('The time that the current revision was created.'),
      'type' => 'integer_field',
      'queryable' => FALSE,
    );
    $properties['revision_uid'] = array(
      'label' => t('Revision user ID'),
      'description' => t('The user ID of the author of the current revision.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
      'queryable' => FALSE,
    );
    $properties['log'] = array(
      'label' => t('Log'),
      'description' => t('The log entry explaining the changes in this version.'),
      'type' => 'string_field',
    );
    return $properties;
  }

}
