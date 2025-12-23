<?php

/**
 * @file
 * Post update functions for announcements_feed.
 */

/**
 * Migrates last fetch timestamp from State API to key/value storage.
 */
function announcements_feed_post_update_migrate_last_fetch_state_to_keyvalue(): void {
  $state = \Drupal::state();
  $last_fetch = (int) $state->get('announcements_feed.last_fetch', 0);
  \Drupal::keyValue('announcements_feed')->set('last_fetch', $last_fetch);
  $state->delete('announcements_feed.last_fetch');
}
