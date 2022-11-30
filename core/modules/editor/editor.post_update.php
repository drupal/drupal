<?php

/**
 * @file
 * Post update functions for Editor.
 */

use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatInterface;
use Drupal\filter\FilterPluginCollection;

/**
 * Implements hook_removed_post_updates().
 */
function editor_removed_post_updates() {
  return [
    'editor_post_update_clear_cache_for_file_reference_filter' => '9.0.0',
  ];
}

/**
 * Enable filter_image_lazy_load if editor_file_reference is enabled.
 */
function editor_post_update_image_lazy_load(): void {
  if (\Drupal::service('plugin.manager.filter')->hasDefinition('editor_file_reference')) {
    foreach (FilterFormat::loadMultiple() as $format) {
      assert($format instanceof FilterFormatInterface);
      $collection = $format->filters();
      $configuration = $collection->getConfiguration();
      assert($collection instanceof FilterPluginCollection);
      if (array_key_exists('editor_file_reference', $configuration)) {
        $collection->addInstanceId('filter_image_lazy_load');
        $configuration['filter_image_lazy_load'] = [
          'id' => 'filter_image_lazy_load',
          'provider' => 'editor',
          'status' => TRUE,
          // Place lazy loading after editor file reference.
          'weight' => $configuration['editor_file_reference']['weight'] + 1,
          'settings' => [],
        ];
        $collection->setConfiguration($configuration);
        $format->save();
      }
    }
  }
}
