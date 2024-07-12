<?php

/**
 * @file
 * Post update functions for System.
 */

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Config\Schema\Mapping;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFormModeInterface;
use Drupal\Core\Entity\EntityViewModeInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\StringTranslation\TranslatableMarkup;

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
function system_post_update_timestamp_formatter(?array &$sandbox = NULL): void {
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
function system_post_update_add_description_to_entity_view_mode(?array &$sandbox = NULL): void {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityViewModeInterface $entity_view_mode) {
    return $entity_view_mode->get('description') === NULL;
  };

  $config_entity_updater->update($sandbox, 'entity_view_mode', $callback);
}

/**
 * Update description for form modes.
 */
function system_post_update_add_description_to_entity_form_mode(?array &$sandbox = NULL): void {
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

/**
 * Fix path in README.txt in CONFIG_SYNC_DIRECTORY.
 */
function system_post_update_amend_config_sync_readme_url() {
  $configuration_directory = Settings::get('config_sync_directory');
  $readme_path = $configuration_directory . '/README.txt';
  if (!file_exists($readme_path)) {
    // No operation if the original file is not there.
    return;
  }
  $writable = is_writable($readme_path) || (!file_exists($readme_path) && is_writable($configuration_directory));
  if (!$writable) {
    // Cannot write the README.txt file, nothing to do.
    return;
  }
  $original_content = file_get_contents($readme_path);
  $changed_content = str_replace('admin/config/development/configuration/sync', 'admin/config/development/configuration', $original_content);
  file_put_contents($readme_path, $changed_content);
  return \t('Amended configuration synchronization readme file content.');
}

/**
 * Adds default value for the mail_notification config parameter.
 */
function system_post_update_mail_notification_setting() {
  $config = \Drupal::configFactory()->getEditable('system.site');
  // If the value doesn't exist it always returns NULL.
  if (is_null($config->get('mail_notification'))) {
    $config->set('mail_notification', NULL)->save();
  }
}

/**
 * Fix system.cron:logging values to boolean.
 */
function system_post_update_set_cron_logging_setting_to_boolean(): void {
  $config = \Drupal::configFactory()->getEditable('system.cron');
  $logging = $config->get('logging');
  if (!is_bool($logging)) {
    $config->set('logging', (bool) $logging)->save();
  }
}

/**
 * Adds a langcode to all simple config which needs it.
 */
function system_post_update_add_langcode_to_all_translatable_config(&$sandbox = NULL): TranslatableMarkup {
  $config_factory = \Drupal::configFactory();

  // If this is the first run, populate the sandbox with the names of all
  // config objects.
  if (!isset($sandbox['names'])) {
    $sandbox['names'] = $config_factory->listAll();
    $sandbox['max'] = count($sandbox['names']);
  }

  /** @var \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager */
  $typed_config_manager = \Drupal::service(TypedConfigManagerInterface::class);
  /** @var \Drupal\Core\Config\ConfigManagerInterface $config_manager */
  $config_manager = \Drupal::service(ConfigManagerInterface::class);
  $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

  $names = array_splice($sandbox['names'], 0, Settings::get('entity_update_batch_size', 50));
  foreach ($names as $name) {
    // We're only dealing with simple config, which won't map to an entity type.
    // But if this is a simple config object that has no schema, we can't do
    // anything here and we don't need to, because config must have schema in
    // order to be translatable.
    if ($config_manager->getEntityTypeIdByName($name) || !$typed_config_manager->hasConfigSchema($name)) {
      continue;
    }

    $config = \Drupal::configFactory()->getEditable($name);
    $typed_config = $typed_config_manager->createFromNameAndData($name, $config->getRawData());
    // Simple config is always a mapping.
    assert($typed_config instanceof Mapping, "Failed on config name '$name'");

    // If this config contains any elements (at any level of nesting) which
    // are translatable, but the config hasn't got a langcode, assign one. But
    // if nothing in the config structure is translatable, the config shouldn't
    // have a langcode at all.
    if ($typed_config->hasTranslatableElements()) {
      if ($config->get('langcode')) {
        continue;
      }
      $config->set('langcode', $default_langcode);
    }
    else {
      if (!array_key_exists('langcode', $config->get())) {
        continue;
      }
      $config->clear('langcode');
    }
    $config->save();
  }

  $sandbox['#finished'] = empty($sandbox['max']) || empty($sandbox['names']) ? 1 : ($sandbox['max'] - count($sandbox['names'])) / $sandbox['max'];
  if ($sandbox['#finished'] === 1) {
    return new TranslatableMarkup('Finished updating simple config langcodes.');
  }
  return new PluralTranslatableMarkup($sandbox['max'] - count($sandbox['names']),
    'Processed @count items of @total.',
    'Processed @count items of @total.',
    ['@total' => $sandbox['max']],
  );
}

/**
 * Move development settings from state to raw key-value storage.
 */
function system_post_update_move_development_settings_to_keyvalue(): void {
  $state = \Drupal::state();
  $development_settings = $state->getMultiple([
    'twig_debug',
    'twig_cache_disable',
    'disable_rendered_output_cache_bins',
  ]);
  \Drupal::keyValue('development_settings')->setMultiple($development_settings);
  $state->deleteMultiple(array_keys($development_settings));
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
