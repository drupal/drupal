<?php

/**
 * @file
 * Post update functions for Node.
 */

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\views\Entity\View;

/**
 * Implements hook_removed_post_updates().
 */
function node_removed_post_updates() {
  return [
    'node_post_update_configure_status_field_widget' => '9.0.0',
    'node_post_update_node_revision_views_data' => '9.0.0',
  ];
}

/**
 * Add a published filter to the glossary View.
 */
function node_post_update_glossary_view_published() {
  if (\Drupal::moduleHandler()->moduleExists('views')) {
    $view = View::load('glossary');
    if (!$view) {
      return;
    }
    $display =& $view->getDisplay('default');
    if (!isset($display['display_options']['filters']['status'])) {
      $display['display_options']['filters']['status'] = [
        'expose' => [
          'operator' => '',
          'operator_limit_selection' => FALSE,
          'operator_list' => [],
        ],
        'field' => 'status',
        'group' => 1,
        'id' => 'status',
        'table' => 'node_field_data',
        'value' => '1',
        'plugin_id' => 'boolean',
        'entity_type' => 'node',
        'entity_field' => 'status',
      ];
      $view->save();
    }
  }
}

/**
 * Rebuild the node revision routes.
 */
function node_post_update_rebuild_node_revision_routes() {
  // Empty update to rebuild routes.
}

/**
 * Updates stale references to Drupal\node\Entity\Node::getCurrentUserId.
 */
function node_post_update_modify_base_field_author_override() {
  $uid_fields = \Drupal::entityTypeManager()
    ->getStorage('base_field_override')
    ->getQuery()
    ->condition('entity_type', 'node')
    ->condition('field_name', 'uid')
    ->condition('default_value_callback', 'Drupal\node\Entity\Node::getCurrentUserId')
    ->execute();
  foreach (BaseFieldOverride::loadMultiple($uid_fields) as $base_field_override) {
    $base_field_override->setDefaultValueCallback('Drupal\node\Entity\Node::getDefaultEntityOwner')->save();
  }
}
