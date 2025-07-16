<?php

/**
 * @file
 * Post update functions for Node.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Implements hook_removed_post_updates().
 */
function node_removed_post_updates(): array {
  return [
    'node_post_update_configure_status_field_widget' => '9.0.0',
    'node_post_update_node_revision_views_data' => '9.0.0',
    'node_post_update_glossary_view_published' => '10.0.0',
    'node_post_update_rebuild_node_revision_routes' => '10.0.0',
    'node_post_update_modify_base_field_author_override' => '10.0.0',
    'node_post_update_set_node_type_description_and_help_to_null' => '11.0.0',
  ];
}

/**
 * Creates base field override config for the promote base field on node types.
 */
function node_post_update_create_promote_base_field_overrides(&$sandbox = []): void {
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager */
  $entityFieldManager = \Drupal::service(EntityFieldManagerInterface::class);
  $promoteFieldDefinition = $entityFieldManager->getBaseFieldDefinitions('node')['promote'];
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'node_type', function (NodeTypeInterface $node_type) use ($promoteFieldDefinition): bool {
      $config = $promoteFieldDefinition->getConfig($node_type->id());
      $changed = FALSE;
      if ($config->isNew()) {
        // Set the default value for existing node types that didn't already
        // have a base_field_override to TRUE, maintaining the previous default.
        $config->setDefaultValue(TRUE)->save();
        $changed = TRUE;
      }
      return $changed;
    });
}
