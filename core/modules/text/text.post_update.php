<?php

/**
 * @file
 * Contains post update hooks for the text module.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;

/**
 * Implements hook_removed_post_updates().
 */
function text_removed_post_updates(): array {
  return [
    'text_post_update_add_required_summary_flag' => '9.0.0',
    'text_post_update_add_required_summary_flag_form_display' => '10.0.0',
    'text_post_update_allowed_formats' => '11.0.0',
  ];
}

/**
 * Update field storage dependencies after text_with_summary install.
 */
function text_post_update_update_field_storage_dependencies(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'field_storage_config', function ($field_storage) {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    if ($field_storage->getType() === 'text_with_summary' && $field_storage->getTypeProvider() === 'text') {
      $field_storage->set('module', 'text_with_summary');
      return TRUE;
    }
    return FALSE;
  });
}

/**
 * Update field dependencies after text_with_summary install.
 */
function text_post_update_update_field_dependencies(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'field_config');
}

/**
 * Update form display dependencies after text_with_summary install.
 */
function text_post_update_update_form_display_dependencies(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_form_display');
}

/**
 * Update view display dependencies after text_with_summary install.
 */
function text_post_update_update_view_display_dependencies(array &$sandbox): void {
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'entity_view_display');
}

/**
 * Update view dependencies after text_with_summary install.
 */
function text_post_update_update_view_dependencies(array &$sandbox): void {
  if (!\Drupal::moduleHandler()->moduleExists('views')) {
    return;
  }
  \Drupal::classResolver(ConfigEntityUpdater::class)->update($sandbox, 'view');
}
