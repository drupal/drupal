<?php
// $Id$

/**
 * A stub cache implementation to be used during the installation
 * process when database access is not yet available. Because Drupal's
 * caching system never requires that cached data be present, these
 * stub functions can short-circuit the process and sidestep the
 * need for any persistent storage. Obviously, using this cache
 * implementation during normal operations would have a negative impact
 * on performance.
 */
class DrupalFakeCache implements DrupalCacheInterface {
  function __construct($bin) {
  }

  function get($cid) {
    return FALSE;
  }

  function getMultiple(&$cids) {
    return array();
  }


  function set($cid, $data, $expire = CACHE_PERMANENT, array $headers = NULL) {
  }

  function clear($cid = NULL, $wildcard = FALSE) {
  }
	
}
