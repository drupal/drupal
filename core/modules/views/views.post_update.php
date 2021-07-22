<?php

/**
 * @file
 * Post update functions for Views.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\Entity\View;
use Drupal\views\ViewsConfigUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function views_removed_post_updates() {
  return [
    'views_post_update_update_cacheability_metadata' => '9.0.0',
    'views_post_update_cleanup_duplicate_views_data' => '9.0.0',
    'views_post_update_field_formatter_dependencies' => '9.0.0',
    'views_post_update_taxonomy_index_tid' => '9.0.0',
    'views_post_update_serializer_dependencies' => '9.0.0',
    'views_post_update_boolean_filter_values' => '9.0.0',
    'views_post_update_grouped_filters' => '9.0.0',
    'views_post_update_revision_metadata_fields' => '9.0.0',
    'views_post_update_entity_link_url' => '9.0.0',
    'views_post_update_bulk_field_moved' => '9.0.0',
    'views_post_update_filter_placeholder_text' => '9.0.0',
    'views_post_update_views_data_table_dependencies' => '9.0.0',
    'views_post_update_table_display_cache_max_age' => '9.0.0',
    'views_post_update_exposed_filter_blocks_label_display' => '9.0.0',
    'views_post_update_make_placeholders_translatable' => '9.0.0',
    'views_post_update_limit_operator_defaults' => '9.0.0',
    'views_post_update_remove_core_key' => '9.0.0',
  ];
}

/**
 * Update field names for multi-value base fields.
 */
function views_post_update_field_names_for_multivalue_fields(&$sandbox = NULL) {
  /** @var \Drupal\views\ViewsConfigUpdater $view_config_updater */
  $view_config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
  $view_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) use ($view_config_updater) {
    return $view_config_updater->needsMultivalueBaseFieldUpdate($view);
  });
}

/**
 * Clear errors caused by relationships to configuration entities.
 */
function views_post_update_configuration_entity_relationships() {
  // Empty update to clear Views data.
}

/**
 * Clear caches due to removal of sorting for global custom text field.
 */
function views_post_update_remove_sorting_global_text_field() {
  // Empty post-update hook.
}

/**
 * Fix views containing entity fields with an empty group column value set.
 */
function views_post_update_empty_entity_field_group_column() {
  $views = View::loadMultiple();
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');

  array_walk($views, function (View $view) use ($entity_field_manager) {
    $save = FALSE;
    $displays = $view->get('display');
    foreach ($displays as $display_name => &$display) {
      if (isset($display['display_options']['fields'])) {
        foreach ($display['display_options']['fields'] as &$field) {
          // Only update fields that have group_column set to an empty value.
          if (!empty($field['plugin_id']) && $field['plugin_id'] === 'field' && isset($field['group_column']) && empty($field['group_column'])) {
            // Attempt to load the field storage definition of the field.
            $executable = $view->getExecutable();
            $executable->setDisplay($display_name);
            /** @var \Drupal\views\Plugin\views\field\FieldHandlerInterface $field_handler */
            $field_handler = $executable->getDisplay()
              ->getHandler('field', $field['id']);
            if ($entity_type_id = $field_handler->getEntityType()) {
              $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);

              $field_storage = NULL;
              if (isset($field['field']) && isset($field_storage_definitions[$field['field']])) {
                $field_storage = $field_storage_definitions[$field['field']];
              }
              // If a main property is defined use that as a default.
              if ($field_storage !== NULL && $field_storage->getMainPropertyName()) {
                $save = TRUE;
              }
              elseif ($field_storage !== NULL) {
                $save = TRUE;
              }
            }
          }
        }
      }
    }
    if ($save) {
      $view->save();
    }
  });
}
