<?php
// $Id: cache.inc,v 1.25.4.8 2010/10/26 19:06:26 dereine Exp $
/**
 * @file cache.inc
 *
 * Functions to load Views' data so that it knows what is available to
 * build queries from.
 */

/**
 * Load views files on behalf of modules.
 */
function _views_include_handlers() {
  views_module_include('views.inc');
}

/**
 * Load default views files on behalf of modules.
 */
function _views_include_default_views() {
  views_module_include('views_default.inc');
}

/**
 * Fetch Views' data from the cache
 */
function _views_fetch_data($table = NULL, $reset = FALSE) {
  static $cache = NULL;
  if (!isset($cache) || $reset) {
    $start = microtime(TRUE);
    // NOTE: This happens whether we retrieve them from cache or otherwise.

    $data = views_cache_get('views_data', TRUE);
    if (!empty($data->data)) {
      $cache = $data->data;
    }

    if (empty($cache)) {
      views_include_handlers();
      $cache = module_invoke_all('views_data');
      foreach (module_implements('views_data_alter') as $module) {
        $function = $module . '_views_data_alter';
        $function($cache);
      }

      views_cache_set('views_data', $cache, TRUE);
    }
  }

  if (!$table) {
    return $cache;
  }
  if (isset($cache[$table])) {
    return $cache[$table];
  }

  // Return an empty array if there is no match.
  return array();
}

/**
 * Fetch the plugin data from cache.
 */
function _views_fetch_plugin_data($type = NULL, $plugin = NULL, $reset = FALSE) {
  static $cache = NULL;
  if (!isset($cache) || $reset) {
    $start = microtime(TRUE);
    views_include('plugins');
    views_include_handlers();

    $cache = views_discover_plugins();

  }

  if (!$type && !$plugin) {
    return $cache;
  }
  elseif (!$plugin) {
    // Not in the if above so the else below won't run
    if (isset($cache[$type])) {
      return $cache[$type];
    }
  }
  elseif (isset($cache[$type][$plugin])) {
    return $cache[$type][$plugin];
  }

  // Return an empty array if there is no match.
  return array();
}

/**
 * Scan all modules for default views and rebuild the default views cache.
 *
 * @return An associative array of all known default views.
 */
function _views_discover_default_views($reset = FALSE) {
  static $cache = NULL;

  if (!isset($cache) || $reset) {
    $index = views_cache_get('views_default_views_index', TRUE);

    // Retrieve each cached default view
    if (!$reset && isset($index->data) && is_array($index->data)) {
      $cache = array();
      foreach ($index->data as $view_name) {
        $data = views_cache_get('views_default:' . $view_name, TRUE);
        if (isset($data->data) && is_object($data->data)) {
          $cache[$view_name] = $data->data;
        }
      }
    }
    // If missing index, rebuild the cache
    else {
      views_include_default_views();
      $cache = array();

      foreach (module_implements('views_default_views') as $module) {
        $results = call_user_func($module . "_views_default_views");
        if (!empty($results) && is_array($results)) {
          foreach($results as $name => $view) {
            // Only views with a sufficiently high api version are eligible.
            if (!empty($view->api_version) && $view->api_version >= 2) {
              // Do not cache dead handlers.
              $view->destroy();
              if (!isset($cache[$name])) {
                $cache[$name] = $view;
              }
              else {
                watchdog('view', "View name '@name' is already taken", array('@name' => $name), WATCHDOG_ERROR);
              }
            }
          }
        }
      }

      // Allow modules to modify default views before they are cached.
      drupal_alter('views_default_views', $cache);

      // Cache the index
      $index = array_keys($cache);
      views_cache_set('views_default_views_index', $index, TRUE);

      // Cache each view
      foreach ($cache as $name => $view) {
        views_cache_set('views_default:' . $name, $view, TRUE);
      }
    }
  }

  return $cache;
}

/**
 * Set a cached item in the views cache.
 *
 * This is just a convenience wrapper around cache_set().
 *
 * @param $cid
 *   The cache ID of the data to store.
 * @param $data
 *   The data to store in the cache. Complex data types will be automatically serialized before insertion.
 *   Strings will be stored as plain text and not serialized.
 * @param $use_language
 *   If TRUE, the data will be cached specific to the currently active language.
 */
function views_cache_set($cid, $data, $use_language = FALSE) {
  global $language;

  if (variable_get('views_skip_cache', FALSE)) {
    return;
  }
  if ($use_language) {
    $cid .= ':' . $language->language;
  }

  cache_set($cid, $data, 'cache_views');
}

/**
 * Return data from the persistent views cache.
 *
 * This is just a convenience wrapper around cache_get().
 *
 * @param $cid
 *   The cache ID of the data to retrieve.
 * @param $use_language
 *   If TRUE, the data will be requested specific to the currently active language.
 */
function views_cache_get($cid, $use_language = FALSE) {
  global $language;

  if (variable_get('views_skip_cache', FALSE)) {
    return 0;
  }
  if ($use_language) {
    $cid .= ':' . $language->language;
  }

  return cache_get($cid, 'cache_views');
}
