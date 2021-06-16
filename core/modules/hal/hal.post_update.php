<?php

/**
 * @file
 * Post update functions for Hal.
 */

/**
 * Remove obsolete hal.settings configuration key.
 */
function hal_post_update_delete_settings() {
  \Drupal::configFactory()
    ->getEditable('hal.settings')
    ->clear('bc_file_uri_as_url_normalizer')
    ->save();
}
