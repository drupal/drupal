<?php

/**
 * @file
 * Post-update functions for Locale module.
 */

/**
 * Clear cache to ensure plural translations are removed from it.
 */
function locale_post_update_clear_cache_for_old_translations() {
  // Remove cache of translations, like '@count[2] words'.
}
