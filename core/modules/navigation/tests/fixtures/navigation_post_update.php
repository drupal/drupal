<?php

/**
 * @file
 * Fixture file to test navigation post update.
 */

use Drupal\Core\Database\Database;

$db = Database::getConnection();

// Add post updates for the Layout Builder and Navigation modules.
$existing_post_updates = $db->select('key_value')
  ->fields('key_value', ['value'])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute()
  ->fetchField();

$additional_post_updates = [
  'layout_builder_post_update_rebuild_plugin_dependencies',
  'layout_builder_post_update_add_extra_fields',
  'layout_builder_post_update_section_storage_context_definitions',
  'layout_builder_post_update_overrides_view_mode_annotation',
  'layout_builder_post_update_cancel_link_to_discard_changes_form',
  'layout_builder_post_update_remove_layout_is_rebuilding',
  'layout_builder_post_update_routing_entity_form',
  'layout_builder_post_update_discover_blank_layout_plugin',
  'layout_builder_post_update_routing_defaults',
  'layout_builder_post_update_discover_new_contextual_links',
  'layout_builder_post_update_fix_tempstore_keys',
  'layout_builder_post_update_section_third_party_settings_schema',
  'layout_builder_post_update_layout_builder_dependency_change',
  'layout_builder_post_update_update_permissions',
  'layout_builder_post_update_make_layout_untranslatable',
  'layout_builder_post_update_override_entity_form_controller',
  'layout_builder_post_update_section_storage_context_mapping',
  'layout_builder_post_update_tempstore_route_enhancer',
  'layout_builder_post_update_timestamp_formatter',
  'layout_builder_post_update_enable_expose_field_block_feature_flag',
  'navigation_post_update_update_permissions',
  'navigation_post_update_set_logo_dimensions_default',
  'navigation_post_update_navigation_user_links_menu',
  'navigation_post_update_uninstall_navigation_top_bar',
  'navigation_post_update_refresh_tempstore_repository',
];

$existing_updates = array_merge(unserialize($existing_post_updates), $additional_post_updates);
$db->update('key_value')
  ->fields(['value' => serialize($existing_updates)])
  ->condition('collection', 'post_update')
  ->condition('name', 'existing_updates')
  ->execute();

// Add configuration for the Navigation module.
$db->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'navigation.block_layout',
    'data' => 'a:3:{s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"P5Y3iMx85NwT445BpYIe9fME9nXDF1AXmOJ_QmTnF34";}s:8:"langcode";s:2:"en";s:8:"sections";a:1:{i:0;a:4:{s:9:"layout_id";s:17:"navigation_layout";s:15:"layout_settings";a:1:{s:5:"label";s:0:"";}s:10:"components";a:5:{s:36:"2622e40b-8786-4b8c-8883-19e49da53023";a:5:{s:4:"uuid";s:36:"2622e40b-8786-4b8c-8883-19e49da53023";s:6:"region";s:7:"content";s:13:"configuration";a:4:{s:2:"id";s:20:"navigation_shortcuts";s:5:"label";s:9:"Shortcuts";s:13:"label_display";s:1:"0";s:8:"provider";s:10:"navigation";}s:6:"weight";i:0;s:10:"additional";a:0:{}}s:36:"3ff7be01-c8b0-4444-88f3-2364d7a8054e";a:5:{s:4:"uuid";s:36:"3ff7be01-c8b0-4444-88f3-2364d7a8054e";s:6:"region";s:7:"content";s:13:"configuration";a:6:{s:2:"id";s:23:"navigation_menu:content";s:5:"label";s:7:"Content";s:13:"label_display";s:1:"0";s:8:"provider";s:10:"navigation";s:5:"level";i:1;s:5:"depth";i:2;}s:6:"weight";i:1;s:10:"additional";a:0:{}}s:36:"3f2f743f-856f-404a-9c28-57ee83af4691";a:5:{s:4:"uuid";s:36:"3f2f743f-856f-404a-9c28-57ee83af4691";s:6:"region";s:7:"content";s:13:"configuration";a:6:{s:2:"id";s:21:"navigation_menu:admin";s:5:"label";s:14:"Administration";s:13:"label_display";s:1:"0";s:8:"provider";s:10:"navigation";s:5:"level";i:2;s:5:"depth";i:2;}s:6:"weight";i:2;s:10:"additional";a:0:{}}s:36:"283da777-e051-4571-8089-47633a9ce706";a:5:{s:4:"uuid";s:36:"283da777-e051-4571-8089-47633a9ce706";s:6:"region";s:6:"footer";s:13:"configuration";a:4:{s:2:"id";s:15:"navigation_user";s:5:"label";s:4:"User";s:13:"label_display";s:1:"0";s:8:"provider";s:10:"navigation";}s:6:"weight";i:0;s:10:"additional";a:0:{}}s:36:"6d7080a7-abab-4bad-b960-2459ca892a54";a:5:{s:4:"uuid";s:36:"6d7080a7-abab-4bad-b960-2459ca892a54";s:6:"region";s:6:"footer";s:13:"configuration";a:8:{s:2:"id";s:15:"navigation_link";s:5:"label";s:4:"Help";s:13:"label_display";s:1:"0";s:8:"provider";s:10:"navigation";s:15:"context_mapping";a:0:{}s:5:"title";s:4:"Help";s:3:"uri";s:20:"internal:/admin/help";s:10:"icon_class";s:4:"help";}s:6:"weight";i:-2;s:10:"additional";a:0:{}}}s:20:"third_party_settings";a:0:{}}}}',
  ])
  ->execute();
$db->insert('config')
  ->fields([
    'collection' => '',
    'name' => 'navigation.settings',
    'data' => 'a:2:{s:5:"_core";a:1:{s:19:"default_config_hash";s:43:"FeJ38-AShWZUh_NwJprQueefcE06zSnUa3cw1lOdjTY";}s:4:"logo";a:3:{s:8:"provider";s:7:"default";s:4:"path";s:0:"";s:3:"max";a:3:{s:8:"filesize";i:1048576;s:6:"height";i:40;s:5:"width";i:40;}}}',
  ])
  ->execute();

// Add schema for the Layout Builder, Navigation and MySQLi modules.
$extensions = $db->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'layout_builder',
    'value' => 'i:8602;',
  ])
  ->execute();
$extensions = $db->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'navigation',
    'value' => 'i:11002;',
  ])
  ->execute();
$extensions = $db->insert('key_value')
  ->fields([
    'collection' => 'system.schema',
    'name' => 'mysqli',
    'value' => 'i:8000;',
  ])
  ->execute();

// Remove configuration from the fixture that is not present when the Shortcut
// module is not installed.
$db->delete('config')
  ->condition('collection', '')
  ->condition('name', 'claro.settings')
  ->execute();
$db->delete('config')
  ->condition('collection', '')
  ->condition('name', 'shortcut.set.default')
  ->execute();

// Set the Layout Builder and Navigation modules as installed and the Shortcuts
// module as uninstalled.
$extensions = $db->select('config')
  ->fields('config', ['data'])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute()
  ->fetchField();
$extensions = unserialize($extensions);
$extensions['module']['layout_builder'] = 0;
$extensions['module']['navigation'] = 0;
unset($extensions['module']['shortcut']);
$db->update('config')
  ->fields(['data' => serialize($extensions)])
  ->condition('collection', '')
  ->condition('name', 'core.extension')
  ->execute();
