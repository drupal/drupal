<?php
// $Id: views_plugin_cache_none.inc,v 1.2.4.1 2009/11/02 22:01:27 merlinofchaos Exp $

/**
 * Caching plugin that provides no caching at all.
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
