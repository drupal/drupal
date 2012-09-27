<?php

/**
 * @file
 * Load Views' data so that it knows what is available to build queries from.
 */

/**
 * Fetch Views' data from the cache.
 *
 * @param string|null $table
 *   If null return views data for all tables, else
 * @param bool $move
 *   Under certain circumstances it makes sense to not get the moved table, but the old one.
 *   One example is views_get_handler.
 * @param bool $reset
 *   If $reset is TRUE, rebuild the internal cache.
 *
 * @return array
 *   An array of views data. If $table is NULL if will contains one info array
 *  for each table, else just one info array.
 *
 * @see hook_views_data
 */
function _views_fetch_data($table = NULL, $move = TRUE, $reset = FALSE) {
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
      if (isset($cache[$table]['moved to']) && $move) {
        $moved_table = $cache[$table]['moved to'];
        if (!empty($recursion_protection[$table])) {
          // recursion detected!
          return NULL;
        }
        $recursion_protection[$table] = TRUE;
        $data = _views_fetch_data($moved_table);
        $recursion_protection = array();
        return $data;
      }
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
  views_include_handlers();
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
