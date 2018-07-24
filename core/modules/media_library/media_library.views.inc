<?php

/**
 * @file
 * Contains Views integration for the media_library module.
 */

/**
 * Implements hook_views_data().
 */
function media_library_views_data() {
  $data = [];
  $data['media']['media_library_select_form'] = [
    'title' => t('Select media'),
    'help' => t('Provides a field for selecting media entities in our media library view'),
    'real field' => 'mid',
    'field' => [
      'id' => 'media_library_select_form',
    ],
  ];
  return $data;
}
