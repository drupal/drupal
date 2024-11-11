<?php

/**
 * @file
 * Post update functions for layout discovery.
 */

/**
 * Implements hook_removed_post_updates().
 */
function layout_discovery_removed_post_updates(): array {
  return [
    'layout_discovery_post_update_recalculate_entity_form_display_dependencies' => '9.0.0',
    'layout_discovery_post_update_recalculate_entity_view_display_dependencies' => '9.0.0',
  ];
}
