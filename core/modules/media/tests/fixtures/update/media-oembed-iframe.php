<?php

/**
 * @file
 * Test oembed update by adding an oembed field display without config.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

// Add a remote video media type.
$media_type = [];
$media_type['langcode'] = 'en';
$media_type['status'] = TRUE;
$media_type['dependencies'] = [];
$media_type['id'] = 'remote_video';
$media_type['uuid'] = 'c86d3b5c-b788-49ab-a06c-257895e47731';
$media_type['label'] = 'Remote video';
$media_type['description'] = 'A remotely hosted video from YouTube or Vimeo.';
$media_type['source'] = 'oembed:video';
$media_type['queue_thumbnail_downloads'] = FALSE;
$media_type['new_revision'] = TRUE;
$media_type['source_configuration'] = [
  'source_field' => 'field_media_oembed_video',
  'thumbnails_directory' => 'public://oembed_thumbnails/[date:custom:Y-m]',
  'providers' => [
    'YouTube',
    'Vimeo',
  ],
];
$media_type['field_map'] = [
  'title' => 'name',
];
$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'media.type.remote_video',
    'data' => serialize($media_type),
  ])
  ->execute();


// Add a remote video view display.
$display = [];
$display['langcode'] = 'en';
$display['status'] = TRUE;
$display['dependencies']['config'][] = 'media.type.remote_video';
$display['dependencies']['module'][] = 'media';
$display['id'] = 'media.remote_video.default';
$display['uuid'] = 'ff13f7fb-d493-4d45-a56d-31a1a0762df7';
$display['targetEntityType'] = 'media';
$display['bundle'] = 'remote_video';
$display['mode'] = 'default';
$display['content']['field_media_oembed_video'] = [
  'type' => 'oembed',
  'label' => 'hidden',
  'settings' => [
    'max_width' => 0,
    'max_height' => 0,
  ],
  'third_party_settings' => [],
  'weight' => 0,
  'region' => 'content',
];

$connection->insert('config')
  ->fields([
    'collection',
    'name',
    'data',
  ])
  ->values([
    'collection' => '',
    'name' => 'core.entity_view_display.media.remote_video.default',
    'data' => serialize($display),
  ])
  ->execute();
