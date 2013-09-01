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
    return parent::create($values);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageControllerNG::attachLoad().
   */
  protected function attachLoad(&$queried_entities, $load_revision = FALSE) {
    $queried_entities = $this->mapFromStorageRecords($queried_entities, $load_revision);

    // Create an array of nodes for each content type and pass this to the
    // object type specific callback. To preserve backward-compatibility we
    // pass on BC decorators to node-specific hooks, while we pass on the
    // regular entity objects else.
    $typed_nodes = array();
    foreach ($queried_entities as $id => $node) {
      $typed_nodes[$node->bundle()][$id] = $queried_entities[$id];
    }

    if ($load_revision) {
      $this->loadFieldItems($queried_entities, FIELD_LOAD_REVISION);
    }
    else {
      $this->loadFieldItems($queried_entities, FIELD_LOAD_CURRENT);
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

}
