<?php
// $Id: cache.inc,v 1.1 2010/10/11 22:18:22 sdboyer Exp $

/**
 * @file
 *
 * Plugins to handle cache-indirection.
 *
 * Simple plugin management to allow clients to more tightly control where
 * object caches are stored.
 *
 * CTools provides an object cache mechanism, and it also provides a number
 * of subsystems that are designed to plug into larger systems. When doing
 * caching on multi-step forms (in particular during AJAX operations) these
 * subsystems often need to operate their own cache. In reality, its best
 * for everyone if they are able to piggyback off of the larger cache.
 *
 * This system allows this by registering plugins to control where caches
 * are actually stored. For the most part, the subsystems could care less
 * where the data is fetched from and stored to. All it needs to know is
 * that it can 'get', 'set' and 'clear' caches. Additionally, some caches
 * might need extra operations such as 'lock' and 'finalize', and other
 * operations may be needed based upon the specific uses for the cache
 * plugins.
 *
 * To utilize cache plugins, there are two pieces of data. First, there is
 * the mechanism, which is simply the name of the plugin to use. CTools
 * provides a 'simple' mechanism which goes straight through to the object
 * cache. The second piece of data is the 'key' which is a unique identifier
 * that can be used to find the data needed. Keys can be generated any number
 * of ways, and the plugin must be agnostic about the key itself.
 *
 * That said, the 'mechanism' can be specified as pluginame::data and that
 * data can be used to derive additional data. For example, it is often
 * desirable to NOT store any cached data until original data (i.e, user
 * input) has been received. The data can be used to derive this original
 * data so that when a 'get' is called, if the cache is missed it can create
 * the data needed. This can help prevent unwanted cache entries from
 * building up just by visiting edit UIs without actually modifying anything.
 *
 * Modules wishing to implement cache indirection mechanisms need to implement
 * a plugin of type 'cache' for the module 'ctools' and provide the .inc file.
 * It should provide callbacks for 'cache set', 'cache get', and 'cache clear'.
 * It can provide callbacks for 'break' and 'finalize' if these are relevant
 * to the caching mechanism (i.e, for use with locking caches such as the page
 * manager cache). Other operations may be utilized but at this time are not part
 * of CTools.
 */

/**
 * Fetch data from an indirect cache.
 *
 * @param string $mechanism
 *   A string containing the plugin name, and an optional data element to
 *   send to the plugin separated by two colons.
 *
 * @param string $key
 *   The key used to identify the cache.
 *
 * @return mixed
 *   The cached data. This can be any format as the plugin does not necessarily
 *   have knowledge of what is being cached.
 */
function ctools_cache_get($mechanism, $key) {
  return ctools_cache_operation($mechanism, $key, 'get');
}

/**
 * Store data in an indirect cache.
 *
 * @param string $mechanism
 *   A string containing the plugin name, and an optional data element to
 *   send to the plugin separated by two colons.
 *
 * @param string $key
 *   The key used to identify the cache.
 *
 * @param mixed $object
 *   The data to cache. This can be any format as the plugin does not
 *   necessarily have knowledge of what is being cached.
 */
function ctools_cache_set($mechanism, $key, $object) {
  return ctools_cache_operation($mechanism, $key, 'set', $object);
}

/**
 * Clear data from an indirect cache.
 *
 * @param string $mechanism
 *   A string containing the plugin name, and an optional data element to
 *   send to the plugin separated by two colons.
 *
 * @param string $key
 *   The key used to identify the cache.
 */
function ctools_cache_clear($mechanism, $key) {
  return ctools_cache_operation($mechanism, $key, 'clear', $object);

}

/**
 * Perform a secondary operation on an indirect cache.
 *
 * Additional operations, beyond get, set and clear may be items
 * such as 'break' and 'finalize', which are needed to support cache
 * locking. Other operations may be added by users of the indirect
 * caching functions as needed.
 *
 * @param string $mechanism
 *   A string containing the plugin name, and an optional data element to
 *   send to the plugin separated by two colons.
 *
 * @param string $key
 *   The key used to identify the cache.
 *
 * @param string $op
 *   The operation to call, such as 'break' or 'finalize'.
 *
 * @param mixed $object
 *   The cache data being operated on, in case it is necessary. This is
 *   optional so no references should be used.
 *
 * @return mixed
 *   The operation may or may not return a value.
 */
function ctools_cache_operation($mechanism, $key, $op, $object = NULL) {
  list($plugin, $data) = ctools_cache_find_plugin($mechanism);
  if (empty($plugin)) {
    return;
  }

  $function = ctools_plugin_get_function($plugin, "cache $op");
  if (empty($function)) {
    return;
  }

  return $function($data, $key, $object, $op);
}

/**
 * Take a mechanism and return a plugin and data.
 *
 * @param string $mechanism
 *   A string containing the plugin name, and an optional data element to
 *   send to the plugin separated by two colons.
 *
 * @return array
 *   An array, the first element will be the plugin and the second element
 *   will be the data. If the plugin could not be found, the $plugin will
 *   be NULL.
 */
function ctools_cache_find_plugin($mechanism) {
  if (strpos($mechanism, '::') !== FALSE) {
    // use explode(2) to ensure that the data can contain double
    // colons, just in case.
    list($name, $data) = explode('::', $mechanism, 2);
  }
  else {
    $name = $mechanism;
    $data = '';
  }

  if (empty($name)) {
    return array(NULL, $data);
  }

  ctools_include('plugins');
  $plugin = ctools_get_plugins('ctools', 'cache', $name);
  return array($plugin, $data);
}
