<?php

/**
 * @file
 * Definition of views_plugin_cache_none.
 */

/**
 * Caching plugin that provides no caching at all.
 *
 * @ingroup views_cache_plugins
 */
class views_plugin_cache_none extends views_plugin_cache {
  function cache_start() { /* do nothing */ }

  function summary_title() {
    return t('None');
  }

  function cache_get($type) {
    return FALSE;
  }

  function cache_set($type) { }
}
