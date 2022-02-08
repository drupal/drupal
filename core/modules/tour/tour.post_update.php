<?php

/**
 * @file
 * Post update functions for Tour.
 */

/**
 * Implements hook_removed_post_updates().
 */
function tour_removed_post_updates() {
  return [
    'tour_post_update_joyride_selectors_to_selector_property' => '10.0.0',
  ];
}
