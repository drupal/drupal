<?php

/**
 * @file
 */

use Drupal\content_moderation\ViewsData;

/**
 * Creates a ViewsData object to respond to views hooks.
 *
 * @return \Drupal\content_moderation\ViewsData
 *   The content moderation ViewsData object.
 */
function _content_moderation_views_data_object() {
  return new ViewsData(
    \Drupal::service('entity_type.manager'),
    \Drupal::service('content_moderation.moderation_information')
  );
}
