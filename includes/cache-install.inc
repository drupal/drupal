<?php
// $Id: cache-install.inc,v 1.7 2009/12/30 08:16:55 dries Exp $

/**
 * @file
 * Provides a stub cache implementation to be used during installation.
 */

/**
 * A stub cache implementation to be used during the installation process.
 *
 * The stub implementation is needed when database access is not yet available.
 * Because Drupal's caching system never requires that cached data be present,
 * these stub functions can short-circuit the process and sidestep the need for
 * any persistent storage. Obviously, using this cache implementation during
 * normal operations would have a negative impact on performance.
 */
class DrupalFakeCache extends DrupalDatabaseCache implements DrupalCacheInterface {
  function get($cid) {
    return FALSE;
  }

  function getMultiple(&$cids) {
    return array();
  }

  function set($cid, $data, $expire = CACHE_PERMANENT, array $headers = NULL) {
  }

  function clear($cid = NULL, $wildcard = FALSE) {
    // If there is a database cache, attempt to clear it whenever possible. The
    // reason for doing this is that the database cache can accumulate data
    // during installation due to any full bootstraps that may occur at the
    // same time (for example, AJAX requests triggered by the installer). If we
    // didn't try to clear it whenever this function is called, the data in the
    // cache would become stale; for example, the installer sometimes calls
    // variable_set(), which updates the {variable} table and then clears the
    // cache to make sure that the next page request picks up the new value.
    // Not actually clearing the cache here therefore leads old variables to be
    // loaded on the first page requests after installation, which can cause
    // subtle bugs, some of which would not be fixed unless the site
    // administrator cleared the cache manually.
    try {
      if (function_exists('drupal_install_initialize_database')) {
        drupal_install_initialize_database();
        parent::clear($cid, $wildcard);
      }
    }
    // If the attempt at clearing the cache causes an error, that means that
    // either the database connection is not set up yet or the relevant cache
    // table in the database has not yet been created, so we can safely do
    // nothing here.
    catch (Exception $e) {
    }
  }

  function isEmpty() {
    return TRUE;
  }
}
