<?php

/**
 * @file
 * Post update functions for Layout Builder.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;

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
  ];
}

/**
 * Clear caches due to addition of service decorator for entity form controller.
 */
function layout_builder_post_update_override_entity_form_controller() {
  // Empty post-update hook.
}

/**
 * Update view displays that use Layout Builder to add empty context mappings.
 */
function layout_builder_post_update_section_storage_context_mapping(&$sandbox = []) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);

  $callback = function (EntityViewDisplayInterface $display) {
    $needs_update = FALSE;

    // Only update entity view displays where Layout Builder is enabled.
    if ($display instanceof LayoutEntityDisplayInterface && $display->isLayoutBuilderEnabled()) {
      foreach ($display->getSections() as $section) {
        // Add an empty context mapping to each section where one doesn't exist.
        $section->setLayoutSettings($section->getLayoutSettings() + [
          'context_mapping' => [],
        ]);

        // Flag this display as needing to be updated.
        $needs_update = TRUE;
      }
    }

    return $needs_update;
  };

  $config_entity_updater->update($sandbox, 'entity_view_display', $callback);
}

/**
 * Clear caches due to adding a new route enhancer.
 */
function layout_builder_post_update_tempstore_route_enhancer() {
  // Empty post-update hook.
}
