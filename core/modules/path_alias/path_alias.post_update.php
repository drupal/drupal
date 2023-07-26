<?php

/**
 * @file
 * Post update functions for Path Alias.
 */

/**
 * Remove the path_alias__status index.
 */
function path_alias_post_update_drop_path_alias_status_index(): void {
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $update_manager */
  $update_manager = \Drupal::service('entity.definition_update_manager');
  $entity_type = $update_manager->getEntityType('path_alias');
  $update_manager->updateEntityType($entity_type);
}
