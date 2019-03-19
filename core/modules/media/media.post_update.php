<?php

/**
 * @file
 * Post update functions for Media.
 */

/**
 * Clear caches due to changes in local tasks and action links.
 */
function media_post_update_collection_route() {
  // Empty post-update hook.
}

/**
 * Clear caches due to the addition of a Media-specific entity storage handler.
 */
function media_post_update_storage_handler() {
  // Empty post-update hook.
}

/**
 * Keep media items viewable at /media/{id}.
 */
function media_post_update_enable_standalone_url() {
  $config = \Drupal::configFactory()->getEditable('media.settings');
  if ($config->get('standalone_url') === NULL) {
    $config->set('standalone_url', TRUE)->save(TRUE);
  }
}
