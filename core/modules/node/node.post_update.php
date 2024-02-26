<?php

/**
 * @file
 * Post update functions for Node.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\node\NodeTypeInterface;

/**
 * Converts empty `description` and `help` in content types to NULL.
 */
function node_post_update_set_node_type_description_and_help_to_null(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'node_type', function (NodeTypeInterface $node_type): bool {
      // @see node_node_type_presave()
      return trim($node_type->getDescription()) === '' || trim($node_type->getHelp()) === '';
    });
}

/**
 * Implements hook_removed_post_updates().
 */
function node_removed_post_updates() {
  return [
    'node_post_update_configure_status_field_widget' => '9.0.0',
    'node_post_update_node_revision_views_data' => '9.0.0',
    'node_post_update_glossary_view_published' => '10.0.0',
    'node_post_update_rebuild_node_revision_routes' => '10.0.0',
    'node_post_update_modify_base_field_author_override' => '10.0.0',
  ];
}
