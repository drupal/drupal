<?php

/**
 * @file
 * Post update functions for System.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityViewModeInterface;
use Drupal\Core\Entity\EntityFormModeInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;

/**
 * Implements hook_removed_post_updates().
 */
function system_removed_post_updates() {
  return [
    'system_post_update_recalculate_configuration_entity_dependencies' => '9.0.0',
    'system_post_update_add_region_to_entity_displays' => '9.0.0',
    'system_post_update_hashes_clear_cache' => '9.0.0',
    'system_post_update_timestamp_plugins' => '9.0.0',
    'system_post_update_classy_message_library' => '9.0.0',
    'system_post_update_field_type_plugins' => '9.0.0',
    'system_post_update_field_formatter_entity_schema' => '9.0.0',
    'system_post_update_fix_jquery_extend' => '9.0.0',
    'system_post_update_change_action_plugins' => '9.0.0',
    'system_post_update_change_delete_action_plugins' => '9.0.0',
    'system_post_update_language_item_callback' => '9.0.0',
    'system_post_update_extra_fields' => '9.0.0',
    'system_post_update_states_clear_cache' => '9.0.0',
    'system_post_update_add_expand_all_items_key_in_system_menu_block' => '9.0.0',
    'system_post_update_clear_menu_cache' => '9.0.0',
    'system_post_update_layout_plugin_schema_change' => '9.0.0',
    'system_post_update_entity_reference_autocomplete_match_limit' => '9.0.0',
    'system_post_update_extra_fields_form_display' => '10.0.0',
    'system_post_update_uninstall_simpletest' => '10.0.0',
    'system_post_update_uninstall_entity_reference_module' => '10.0.0',
    'system_post_update_entity_revision_metadata_bc_cleanup' => '10.0.0',
    'system_post_update_uninstall_classy' => '10.0.0',
    'system_post_update_uninstall_stable' => '10.0.0',
    'system_post_update_claro_dropbutton_variants' => '10.0.0',
    'system_post_update_schema_version_int' => '10.0.0',
    'system_post_update_delete_rss_settings' => '10.0.0',
    'system_post_update_remove_key_value_expire_all_index' => '10.0.0',
    'system_post_update_service_advisory_settings' => '10.0.0',
    'system_post_update_delete_authorize_settings' => '10.0.0',
    'system_post_update_sort_all_config' => '10.0.0',
    'system_post_update_enable_provider_database_driver' => '10.0.0',
  ];
}

/**
 * Add new menu linkset endpoint setting.
 */
function system_post_update_linkset_settings() {
  $config = \Drupal::configFactory()->getEditable('system.feature_flags');
  $config->set('linkset_endpoint', FALSE)->save();
}

/**
 * Update timestamp formatter settings for entity view displays.
 */
function system_post_update_timestamp_formatter(array &$sandbox = NULL): void {
  /** @var \Drupal\Core\Field\FormatterPluginManager $field_formatter_manager */
  $field_formatter_manager = \Drupal::service('plugin.manager.field.formatter');

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $entity_view_display) use ($field_formatter_manager): bool {
    $update = FALSE;
    foreach ($entity_view_display->getComponents() as $name => $component) {
      if (empty($component['type'])) {
        continue;
      }

      $plugin_definition = $field_formatter_manager->getDefinition($component['type'], FALSE);
      // Check also potential plugins extending TimestampFormatter.
      if (!is_a($plugin_definition['class'], TimestampFormatter::class, TRUE)) {
        continue;
      }

      // The 'tooltip' and 'time_diff' settings might have been set, with their
      // default values, if this entity has been already saved in a previous
      // (post)update, such as layout_builder_post_update_timestamp_formatter().
      // Ensure that existing timestamp formatters doesn't show any tooltip.
      if (!isset($component['settings']['tooltip']) || !isset($component['settings']['time_diff']) || $component['settings']['tooltip']['date_format'] !== '') {
        // Existing timestamp formatters don't have tooltip.
        $component['settings']['tooltip'] = [
          'date_format' => '',
          'custom_date_format' => '',
        ];
        $entity_view_display->setComponent($name, $component);
        $update = TRUE;
      }
    }
    return $update;
  });
}

/**
 * Enable the password compatibility module.
 */
function system_post_update_enable_password_compatibility() {
  \Drupal::service('module_installer')->install(['phpass']);
}

/**
 * Remove redundant asset state and config.
 */
function system_post_update_remove_asset_entries() {
  \Drupal::state()->delete('drupal_css_cache_files');
  \Drupal::state()->delete('system.js_cache_files');
  $config = \Drupal::configFactory()->getEditable('system.performance');
  $config->clear('stale_file_threshold');
  $config->save();
}

/**
 * Remove redundant asset query string state.
 */
function system_post_update_remove_asset_query_string() {
  \Drupal::state()->delete('system.css_js_query_string');
}

/**
 * Update description for view modes.
 */
function system_post_update_add_description_to_entity_view_mode(array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityViewModeInterface $entity_view_mode) {
    return $entity_view_mode->get('description') === NULL;
  };

  $config_entity_updater->update($sandbox, 'entity_view_mode', $callback);
}

/**
 * Update description for form modes.
 */
function system_post_update_add_description_to_entity_form_mode(array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityFormModeInterface $entity_form_mode) {
    return $entity_form_mode->get('description') === NULL;
  };

  $config_entity_updater->update($sandbox, 'entity_form_mode', $callback);
}

/**
 * Updates system.theme.global:logo.url config if it's still at the default.
 */
function system_post_update_set_blank_log_url_to_null() {
  $global_theme_settings = \Drupal::configFactory()->getEditable('system.theme.global');
  if ($global_theme_settings->get('logo.url') === '') {
    $global_theme_settings
      ->set('logo.url', NULL)
      ->save(TRUE);
  }
}

/**
 * Add new default mail transport dsn.
 */
function system_post_update_mailer_dsn_settings() {
}

/**
 * Add new default mail transport dsn.
 */
function system_post_update_mailer_structured_dsn_settings() {
  $config = \Drupal::configFactory()->getEditable('system.mail');
  $config->set('mailer_dsn', [
    'scheme' => 'sendmail',
    'host' => 'default',
    'user' => NULL,
    'password' => NULL,
    'port' => NULL,
    'options' => [],
  ])->save();
}
