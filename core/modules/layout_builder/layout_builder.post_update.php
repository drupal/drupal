<?php

/**
 * @file
 * Post update functions for Layout Builder.
 */

/**
 * Implements hook_removed_post_updates().
 */
function layout_builder_removed_post_updates() {
  return [
    'layout_builder_post_update_rebuild_plugin_dependencies' => '9.0.0',
    'layout_builder_post_update_add_extra_fields' => '9.0.0',
    'layout_builder_post_update_section_storage_context_definitions' => '9.0.0',
    'layout_builder_post_update_overrides_view_mode_annotation' => '9.0.0',
    'layout_builder_post_update_cancel_link_to_discard_changes_form' => '9.0.0',
    'layout_builder_post_update_remove_layout_is_rebuilding' => '9.0.0',
    'layout_builder_post_update_routing_entity_form' => '9.0.0',
    'layout_builder_post_update_discover_blank_layout_plugin' => '9.0.0',
    'layout_builder_post_update_routing_defaults' => '9.0.0',
    'layout_builder_post_update_discover_new_contextual_links' => '9.0.0',
    'layout_builder_post_update_fix_tempstore_keys' => '9.0.0',
    'layout_builder_post_update_section_third_party_settings_schema' => '9.0.0',
    'layout_builder_post_update_layout_builder_dependency_change' => '9.0.0',
    'layout_builder_post_update_update_permissions' => '9.0.0',
    'layout_builder_post_update_make_layout_untranslatable' => '9.0.0',
    'layout_builder_post_update_override_entity_form_controller' => '10.0.0',
    'layout_builder_post_update_section_storage_context_mapping' => '10.0.0',
    'layout_builder_post_update_tempstore_route_enhancer' => '10.0.0',
    'layout_builder_post_update_timestamp_formatter' => '11.0.0',
    'layout_builder_post_update_enable_expose_field_block_feature_flag' => '11.0.0',
  ];
}
