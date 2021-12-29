<?php

/**
 * @file
 * Post update functions for Media.
 */

use Drupal\Core\Field\Entity\BaseFieldOverride;

/**
 * Implements hook_removed_post_updates().
 */
function media_removed_post_updates() {
  return [
    'media_post_update_collection_route' => '9.0.0',
    'media_post_update_storage_handler' => '9.0.0',
    'media_post_update_enable_standalone_url' => '9.0.0',
    'media_post_update_add_status_extra_filter' => '9.0.0',
  ];
}

/**
 * Updates stale references to Drupal\media\Entity\Media::getCurrentUserId.
 */
function media_post_update_modify_base_field_author_override() {
  $uid_fields = \Drupal::entityTypeManager()
    ->getStorage('base_field_override')
    ->getQuery()
    ->condition('entity_type', 'media')
    ->condition('field_name', 'uid')
    ->condition('default_value_callback', 'Drupal\media\Entity\Media::getCurrentUserId')
    ->execute();
  foreach (BaseFieldOverride::loadMultiple($uid_fields) as $base_field_override) {
    $base_field_override->setDefaultValueCallback('Drupal\media\Entity\Media::getDefaultEntityOwner')->save();
  }
}
