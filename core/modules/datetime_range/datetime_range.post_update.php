<?php

/**
 * @file
 * Post-update functions for Datetime Range module.
 */

/**
 * Clear caches to ensure schema changes are read.
 */
function datetime_range_post_update_translatable_separator() {
  // Empty post-update hook to cause a cache rebuild.
}
