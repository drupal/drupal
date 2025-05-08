<?php

/**
 * @file
 * Post update functions for System.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\EntityFormModeInterface;

/**
 * Implements hook_removed_post_updates().
 */
function system_removed_post_updates(): array {
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
    'system_post_update_linkset_settings' => '11.0.0',
    'system_post_update_enable_password_compatibility' => '11.0.0',
    'system_post_update_remove_asset_entries' => '11.0.0',
    'system_post_update_remove_asset_query_string' => '11.0.0',
    'system_post_update_add_description_to_entity_view_mode' => '11.0.0',
    'system_post_update_add_description_to_entity_form_mode' => '11.0.0',
    'system_post_update_set_blank_log_url_to_null' => '11.0.0',
    'system_post_update_mailer_dsn_settings' => '11.0.0',
    'system_post_update_mailer_structured_dsn_settings' => '11.0.0',
    'system_post_update_amend_config_sync_readme_url' => '11.0.0',
    'system_post_update_mail_notification_setting' => '11.0.0',
    'system_post_update_set_cron_logging_setting_to_boolean' => '11.0.0',
    'system_post_update_move_development_settings_to_keyvalue' => '11.0.0',
    'system_post_update_add_langcode_to_all_translatable_config' => '11.0.0',
  ];
}

/**
 * Updates system.date config to NULL for empty country and timezone defaults.
 */
function system_post_update_convert_empty_country_and_timezone_settings_to_null(): void {
  $system_date_settings = \Drupal::configFactory()->getEditable('system.date');
  $changed = FALSE;
  if ($system_date_settings->get('country.default') === '') {
    $system_date_settings->set('country.default', NULL);
    $changed = TRUE;
  }
  if ($system_date_settings->get('timezone.default') === '') {
    $system_date_settings->set('timezone.default', NULL);
    $changed = TRUE;
  }
  if ($changed) {
    $system_date_settings->save();
  }
}

/**
 * Uninstall the sdc module if installed.
 */
function system_post_update_sdc_uninstall(): void {
  if (\Drupal::moduleHandler()->moduleExists('sdc')) {
    \Drupal::service('module_installer')->uninstall(['sdc'], FALSE);
  }
}

/**
 * Rebuild the container to fix HTML in RSS feeds.
 */
function system_post_update_remove_rss_cdata_subscriber(): void {
  // Empty update to trigger container rebuild.
}

/**
 * Remove path key in system.file.
 */
function system_post_update_remove_path_key(): void {
  if (\Drupal::config('system.file')->get('path') !== NULL) {
    \Drupal::configFactory()->getEditable('system.file')
      ->clear('path')
      ->save();
  }
}

/**
 * Updates entity_form_mode descriptions from empty string to null.
 */
function system_post_update_convert_empty_description_entity_form_modes_to_null(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'entity_form_mode', function (EntityFormModeInterface $form_mode): bool {
      // Entity form mode's `description` field must be stored as NULL at the
      // config level if they are empty.
      if ($form_mode->get('description') !== NULL && trim($form_mode->get('description')) === '') {
        $form_mode->set('description', NULL);
        return TRUE;
      }
      return FALSE;
    });

}
