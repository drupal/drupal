<?php

/**
 * @file
 * Post update functions for Views.
 */

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewsConfigUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function views_removed_post_updates(): array {
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
    'views_post_update_field_names_for_multivalue_fields' => '10.0.0',
    'views_post_update_configuration_entity_relationships' => '10.0.0',
    'views_post_update_rename_default_display_setting' => '10.0.0',
    'views_post_update_remove_sorting_global_text_field' => '10.0.0',
    'views_post_update_title_translations' => '10.0.0',
    'views_post_update_sort_identifier' => '10.0.0',
    'views_post_update_provide_revision_table_relationship' => '10.0.0',
    'views_post_update_image_lazy_load' => '10.0.0',
    'views_post_update_boolean_custom_titles' => '11.0.0',
    'views_post_update_oembed_eager_load' => '11.0.0',
    'views_post_update_responsive_image_lazy_load' => '11.0.0',
    'views_post_update_timestamp_formatter' => '11.0.0',
    'views_post_update_fix_revision_id_part' => '11.0.0',
    'views_post_update_add_missing_labels' => '11.0.0',
    'views_post_update_remove_skip_cache_setting' => '11.0.0',
    'views_post_update_remove_default_argument_skip_url' => '11.0.0',
    'views_post_update_taxonomy_filter_user_context' => '11.0.0',
    'views_post_update_pager_heading' => '11.0.0',
    'views_post_update_rendered_entity_field_cache_metadata' => '11.0.0',
  ];
}

/**
 * Post update configured views for entity reference argument plugin IDs.
 */
function views_post_update_views_data_argument_plugin_id(?array &$sandbox = NULL): void {
  /** @var \Drupal\views\ViewsConfigUpdater $view_config_updater */
  $view_config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
  $view_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($view_config_updater): bool {
    return $view_config_updater->needsEntityArgumentUpdate($view);
  });
}

/**
 * Clean-up empty remember_roles display settings for views filters.
 */
function views_post_update_update_remember_role_empty(?array &$sandbox = NULL): void {
  /** @var \Drupal\views\ViewsConfigUpdater $view_config_updater */
  $view_config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
  $view_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($view_config_updater): bool {
    return $view_config_updater->needsRememberRolesUpdate($view);
  });
}

/**
 * Adds a default table CSS class.
 */
function views_post_update_table_css_class(?array &$sandbox = NULL): void {
  /** @var \Drupal\views\ViewsConfigUpdater $view_config_updater */
  $view_config_updater = \Drupal::classResolver(ViewsConfigUpdater::class);
  $view_config_updater->setDeprecationsEnabled(FALSE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view', function (ViewEntityInterface $view) use ($view_config_updater): bool {
    return $view_config_updater->needsTableCssClassUpdate($view);
  });
}

/**
 * Defaults `items_per_page` to NULL in Views blocks.
 */
function views_post_update_block_items_per_page(?array &$sandbox = NULL): void {
  if (!\Drupal::moduleHandler()->moduleExists('block')) {
    return;
  }
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'block', function (BlockInterface $block): bool {
      if (str_starts_with($block->getPluginId(), 'views_block:')) {
        $settings = $block->get('settings');
        if ($settings['items_per_page'] === 'none') {
          $settings['items_per_page'] = NULL;
          $block->set('settings', $settings);
          return TRUE;
        }
      }
      return FALSE;
    });
}
