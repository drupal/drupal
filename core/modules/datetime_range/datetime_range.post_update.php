<?php

/**
 * @file
 * Post-update functions for Datetime Range module.
 */

use Drupal\views\Views;

/**
 * Clear caches to ensure schema changes are read.
 */
function datetime_range_post_update_translatable_separator() {
  // Empty post-update hook to cause a cache rebuild.
}

/**
 * Update existing views using datetime_range fields.
 */
function datetime_range_post_update_views_string_plugin_id() {

  /* @var \Drupal\views\Entity\View[] $views */
  $views = \Drupal::entityTypeManager()->getStorage('view')->loadMultiple();
  $config_factory = \Drupal::configFactory();
  $message = NULL;
  $ids = [];

  foreach ($views as $view) {
    $displays = $view->get('display');
    $needs_bc_layer_update = FALSE;

    foreach ($displays as $display_name => $display) {

      // Check if datetime_range filters need updates.
      if (!$needs_bc_layer_update && isset($display['display_options']['filters'])) {
        foreach ($display['display_options']['filters'] as $field_name => $filter) {
          if ($filter['plugin_id'] == 'string') {

            // Get field config.
            $filter_views_data = Views::viewsData()->get($filter['table'])[$filter['field']]['filter'];
            if (!isset($filter_views_data['entity_type']) || !isset($filter_views_data['field_name'])) {
              continue;
            }
            $field_storage_name = 'field.storage.' . $filter_views_data['entity_type'] . '.' . $filter_views_data['field_name'];
            $field_configuration = $config_factory->get($field_storage_name);

            if ($field_configuration->get('type') == 'daterange') {
              // Trigger the BC layer control.
              $needs_bc_layer_update = TRUE;
              continue 2;
            }
          }
        }
      }

      // Check if datetime_range sort handlers need updates.
      if (!$needs_bc_layer_update && isset($display['display_options']['sorts'])) {
        foreach ($display['display_options']['sorts'] as $field_name => $sort) {
          if ($sort['plugin_id'] == 'standard') {

            // Get field config.
            $sort_views_data = Views::viewsData()->get($sort['table'])[$sort['field']]['sort'];
            if (!isset($sort_views_data['entity_type']) || !isset($sort_views_data['field_name'])) {
              continue;
            }
            $field_storage_name = 'field.storage.' . $sort_views_data['entity_type'] . '.' . $sort_views_data['field_name'];
            $field_configuration = $config_factory->get($field_storage_name);

            if ($field_configuration->get('type') == 'daterange') {
              // Trigger the BC layer control.
              $needs_bc_layer_update = TRUE;
              continue 2;
            }
          }
        }
      }
    }

    // If current view needs BC layer updates save it and the hook view_presave
    // will do the rest.
    if ($needs_bc_layer_update) {
      $view->save();
      $ids[] = $view->id();
    }
  }

  if (!empty($ids)) {
    $message = \Drupal::translation()->translate('Updated datetime_range filter/sort plugins for views: @ids', ['@ids' => implode(', ', array_unique($ids))]);
  }

  return $message;
}
