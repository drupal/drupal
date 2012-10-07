<?php

/**
 * @file
 * Load Views' data so that it knows what is available to build queries from.
 */

/**
 * Fetch Views' data from the cache.
 *
 * $param string|null $table
 *   (optional) The name of the table for which to fetch Views' data. If
 *   NULL, data for all tables will be retrieved
 * @param bool $reset
 *   (optional) Whether to rebuild the cache. Defaults to FALSE.
 *
 * @return array
 *   An associative array of views data for the given table. If $table is
 *   NULL, the array will be keyed by table name, with each key corresponding
 *   to the data array for that table.
 *
 * @see hook_views_data()
 */
function _views_fetch_data($table = NULL, $reset = FALSE) {
  $cache = &drupal_static(__FUNCTION__ . '_cache');
  $recursion_protection = &drupal_static(__FUNCTION__ . '_recursion_protected');
  $fully_loaded = &drupal_static(__FUNCTION__ . '_fully_loaded');
  if ($reset) {
    $cache = NULL;
    $fully_loaded = FALSE;
  }
  if ($table) {
    if (!isset($cache[$table])) {
      $cid = 'views_data:' . $table;
      $data = views_cache_get($cid, TRUE);
      if (!empty($data->data)) {
        $cache[$table] = $data->data;
      }
      else {
        // No cache entry, rebuild.
        $cache = _views_fetch_data_build();
        $fully_loaded = TRUE;
      }
    }
    if (isset($cache[$table])) {
      return $cache[$table];
    }
  }
  else {
    if (!$fully_loaded) {
      $data = views_cache_get('views_data', TRUE);
      if (!empty($data->data)) {
        $cache = $data->data;
      }

      if (empty($cache)) {
        $cache = _views_fetch_data_build();
      }
      $fully_loaded = TRUE;
    }
    return $cache;
  }
  // Return an empty array if there is no match.
  return array();
}

/**
 * Build, set the views data cache if empty and return the views data.
 *
 * @return array
 *   The views_data of all tables.
 */
function _views_fetch_data_build() {
  $cache = module_invoke_all('views_data');
  foreach (module_implements('views_data_alter') as $module) {
    $function = $module . '_views_data_alter';
    $function($cache);
  }
  _views_data_process_entity_types($cache);

  // Keep a record with all data.
  views_cache_set('views_data', $cache, TRUE);
  // Save data in seperate cache entries.
  foreach ($cache as $key => $data) {
    $cid = 'views_data:' . $key;
    views_cache_set($cid, $data, TRUE);
  }
  return $cache;
}

/**
 * Links tables having an 'entity type' specified to the respective generic entity-type tables.
 */
function _views_data_process_entity_types(&$data) {
  foreach ($data as $table_name => $table_info) {
    // Add in a join from the entity-table if an entity-type is given.
    if (!empty($table_info['table']['entity type'])) {
      $entity_table = 'views_entity_' . $table_info['table']['entity type'];

      $data[$entity_table]['table']['join'][$table_name] = array(
        'left_table' => $table_name,
      );
      $data[$entity_table]['table']['entity type'] = $table_info['table']['entity type'];
      // Copy over the default table group if we have none yet.
      if (!empty($table_info['table']['group']) && empty($data[$entity_table]['table']['group'])) {
        $data[$entity_table]['table']['group'] = $table_info['table']['group'];
      }
    }
  }
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
  if (config('views.settings')->get('skip_cache')) {
    return;
  }
  if ($use_language) {
    $cid .= ':' . language(LANGUAGE_TYPE_INTERFACE)->langcode;
  }

  cache('views_info')->set($cid, $data);
}

/**
 * Return data from the persistent views cache.
 *
 * This is just a convenience wrapper around cache_get().
 *
 * @param int $cid
 *   The cache ID of the data to retrieve.
 * @param bool $use_language
 *   If TRUE, the data will be requested specific to the currently active language.
 *
 * @return stdClass|bool
 *   The cache or FALSE on failure.
 */
function views_cache_get($cid, $use_language = FALSE) {
  if (config('views.settings')->get('skip_cache')) {
    return FALSE;
  }
  if ($use_language) {
    $cid .= ':' . language(LANGUAGE_TYPE_INTERFACE)->langcode;
  }

  return cache('views_info')->get($cid);
}
