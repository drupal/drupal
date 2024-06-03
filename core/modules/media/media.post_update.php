<?php

/**
 * @file
 * Post update functions for Media.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\media\MediaConfigUpdater;
use Drupal\media\MediaTypeInterface;

/**
 * Implements hook_removed_post_updates().
 */
function media_removed_post_updates() {
  return [
    'media_post_update_collection_route' => '9.0.0',
    'media_post_update_storage_handler' => '9.0.0',
    'media_post_update_enable_standalone_url' => '9.0.0',
    'media_post_update_add_status_extra_filter' => '9.0.0',
    'media_post_update_modify_base_field_author_override' => '10.0.0',
  ];
}

/**
 * Add the oEmbed loading attribute setting to field formatter instances.
 */
function media_post_update_oembed_loading_attribute(?array &$sandbox = NULL): void {
  $media_config_updater = \Drupal::classResolver(MediaConfigUpdater::class);
  assert($media_config_updater instanceof MediaConfigUpdater);
  $media_config_updater->setDeprecationsEnabled(TRUE);
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display', function (EntityViewDisplayInterface $view_display) use ($media_config_updater): bool {
    return $media_config_updater->processOembedEagerLoadField($view_display);
  });
}

/**
 * Updates media.settings:iframe_domain config if it's still at the default.
 */
function media_post_update_set_blank_iframe_domain_to_null() {
  $media_settings = \Drupal::configFactory()->getEditable('media.settings');
  if ($media_settings->get('iframe_domain') === '') {
    $media_settings
      ->set('iframe_domain', NULL)
      ->save(TRUE);
  }
}

/**
 * Make sure no Media types are using the source field in the meta mappings.
 */
function media_post_update_remove_mappings_targeting_source_field(?array &$sandbox = NULL): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)
    ->update($sandbox, 'media_type', function (MediaTypeInterface $media_type): bool {
      $source_field = $media_type->getSource()
        ->getSourceFieldDefinition($media_type);

      if ($source_field) {
        $source_field_name = $source_field->getName();

        $original_field_map = $media_type->getFieldMap();
        $field_map = array_diff($original_field_map, [$source_field_name]);

        // Check if old field map matches new field map.
        if (empty(array_diff($original_field_map, $field_map))) {
          return FALSE;
        }

        $media_type->setFieldMap($field_map);
        return TRUE;
      }

      return FALSE;
    });
}
