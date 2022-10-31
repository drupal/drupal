<?php

/**
 * @file
 * Post update functions for Views.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewEntityInterface;
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
  return \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function ($view) use ($view_config_updater) {
    return $view_config_updater->needsMultivalueBaseFieldUpdate($view);
  }, TRUE);
}

/**
 * Clear errors caused by relationships to configuration entities.
 */
function views_post_update_configuration_entity_relationships() {
  // Empty update to clear Views data.
}

/**
 * Rename the setting for showing the default display to 'default_display'.
 */
function views_post_update_rename_default_display_setting() {
  $config = \Drupal::configFactory()->getEditable('views.settings');
  $config->set('ui.show.default_display', $config->get('ui.show.master_display'));
  $config->clear('ui.show.master_display');
  $config->save();
}

/**
 * Clear caches due to removal of sorting for global custom text field.
 */
function views_post_update_remove_sorting_global_text_field() {
  // Empty post-update hook.
}

/**
 * Rebuild routes to fix view title translations.
 */
function views_post_update_title_translations() {
  \Drupal::service('router.builder')->setRebuildNeeded();
}

/**
 * Add the identifier option to all sort handler configurations.
 */
function views_post_update_sort_identifier(?array &$sandbox = NULL): void {
  /** @var \Drupal\views\ViewsConfigUpdater $view_config_updater */
  $view_config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($view_config_updater): bool {
    return $view_config_updater->needsSortFieldIdentifierUpdate($view);
  });
}

/**
 * Clear caches due to adding a relationship from revision table to base table.
 */
function views_post_update_provide_revision_table_relationship() {
  // Empty post-update hook.
}

/**
 * Add lazy load options to all image type field configurations.
 */
function views_post_update_image_lazy_load(?array &$sandbox = NULL): void {
  /** @var \Drupal\views\ViewsConfigUpdater $view_config_updater */
  $view_config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($view_config_updater): bool {
    return $view_config_updater->needsImageLazyLoadFieldUpdate($view);
  }, TRUE);
}
