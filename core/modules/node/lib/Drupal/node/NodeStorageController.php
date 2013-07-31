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

    // Besides the list of nodes, pass one additional argument to
    // hook_node_load(), containing a list of node types that were loaded.
    $argument = array_keys($typed_nodes);
    $this->hookLoadArguments = array($argument);

    // Call hook_entity_load().
    foreach (\Drupal::moduleHandler()->getImplementations('entity_load') as $module) {
      $function = $module . '_entity_load';
      $function($queried_entities, $this->entityType);
    }
    // Call hook_TYPE_load(). The first argument for hook_TYPE_load() are
    // always the queried entities, followed by additional arguments set in
    // $this->hookLoadArguments.
    $args = array_merge(array($queried_entities), $this->hookLoadArguments);
    foreach (\Drupal::moduleHandler()->getImplementations($this->entityType . '_load') as $module) {
      call_user_func_array($module . '_' . $this->entityType . '_load', $args);
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::invokeHook().
   */
  protected function invokeHook($hook, EntityInterface $node) {
    $node = $node->getUntranslated()->getBCEntity();

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
      'type' => 'uuid_field',
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
      'required' => TRUE,
      'settings' => array(
        'default_value' => '',
      ),
      'property_constraints' => array(
        'value' => array('Length' => array('max' => 255)),
      ),
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID of the node author.'),
      'type' => 'entity_reference_field',
      'settings' => array(
        'target_type' => 'user',
        'default_value' => 0,
      ),
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
      'property_constraints' => array(
        'value' => array('NodeChanged' => array()),
      ),
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
