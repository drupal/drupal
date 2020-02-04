<?php

/**
 * @file
 * Post update functions for Serialization module.
 */

/**
 * Remove obsolete serialization.settings configuration.
 */
function serialization_post_update_delete_settings() {
  \Drupal::configFactory()->getEditable('serialization.settings')->delete();
}
