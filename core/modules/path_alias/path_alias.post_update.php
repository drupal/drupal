<?php

/**
 * @file
 * Post update functions for Path Alias.
 */

/**
 * Implements hook_removed_post_updates().
 */
function path_alias_removed_post_updates(): array {
  return [
    'path_alias_post_update_drop_path_alias_status_index' => '11.0.0',
  ];
}

/**
 * Update the path_alias_revision indices.
 */
function path_alias_post_update_update_path_alias_revision_indexes(): void {
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::service('entity.definition_update_manager');
  $entity_type = $update_manager->getEntityType('path_alias');
  $update_manager->updateEntityType($entity_type);
}
