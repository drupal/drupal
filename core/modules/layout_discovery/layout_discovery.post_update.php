<?php

/**
 * @file
 * Post update functions for layout discovery.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Recalculate dependencies for the entity_form_display entity.
 */
function layout_discovery_post_update_recalculate_entity_form_display_dependencies(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_form_display');
}

/**
 * Recalculate dependencies for the entity_view_display entity.
 */
function layout_discovery_post_update_recalculate_entity_view_display_dependencies(&$sandbox = NULL) {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display');
}
