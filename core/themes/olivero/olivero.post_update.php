<?php

/**
 * @file
 * Post update functions for Olivero.
 */

/**
 * Sets the default `base_primary_color` value of Olivero's theme settings.
 */
function olivero_post_update_add_olivero_primary_color() {
  \Drupal::configFactory()->getEditable('olivero.settings')
    ->set('base_primary_color', '#1b9ae4')
    ->save();
}
