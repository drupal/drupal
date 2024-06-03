<?php

/**
 * @file
 * Post update functions for Layout Builder.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\TimestampFormatter;
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
    'layout_builder_post_update_override_entity_form_controller' => '10.0.0',
    'layout_builder_post_update_section_storage_context_mapping' => '10.0.0',
    'layout_builder_post_update_tempstore_route_enhancer' => '10.0.0',
  ];
}

/**
 * Update timestamp formatter settings for Layout Builder fields.
 */
function layout_builder_post_update_timestamp_formatter(?array &$sandbox = NULL): void {
  /** @var \Drupal\Core\Field\FormatterPluginManager $field_formatter_manager */
  $field_formatter_manager = \Drupal::service('plugin.manager.field.formatter');

  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $entity_view_display) use ($field_formatter_manager): bool {
    $update = FALSE;
    if ($entity_view_display instanceof LayoutEntityDisplayInterface && $entity_view_display->isLayoutBuilderEnabled()) {
      foreach ($entity_view_display->getSections() as $section) {
        foreach ($section->getComponents() as $component) {
          if (str_starts_with($component->getPluginId(), 'field_block:')) {
            $configuration = $component->get('configuration');
            $formatter =& $configuration['formatter'];
            if ($formatter && isset($formatter['type'])) {
              $plugin_definition = $field_formatter_manager->getDefinition($formatter['type'], FALSE);
              // Check also potential plugins extending TimestampFormatter.
              if ($plugin_definition && is_a($plugin_definition['class'], TimestampFormatter::class, TRUE)) {
                if (!isset($formatter['settings']['tooltip']) || !isset($formatter['settings']['time_diff'])) {
                  $update = TRUE;
                  // No need to check the rest of components.
                  break 2;
                }
              }
            }
          }
        }
      }
    }
    return $update;
  });
}

/**
 * Enable the expose all fields feature flag module.
 */
function layout_builder_post_update_enable_expose_field_block_feature_flag(): void {
  \Drupal::service('module_installer')->install(['layout_builder_expose_all_field_blocks']);
}
