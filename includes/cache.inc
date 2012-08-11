<?php

/**
 * @file
 * Load Views' data so that it knows what is available to build queries from.
 */

use Drupal\views\Plugin\Type\ViewsPluginManager;

/**
 * Fetch Views' data from the cache
 *
 * @param $move
 *   Under certain circumstances it makes sense to not get the moved table, but the old one.
 *   One example is views_get_handler.
 */
function _views_fetch_data($table = NULL, $move = TRUE, $reset = FALSE) {
  $cache = &drupal_static(__FUNCTION__ . '_cache');
  $recursion_protection = &drupal_static(__FUNCTION__ . '_recursion_protected');
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
      _views_data_process_entity_types($cache);

      views_cache_set('views_data', $cache, TRUE);
    }
  }

  if (!$table) {
    return $cache;
  }
  if (isset($cache[$table])) {
    // Support old views_data entries conversion.
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
    else {
      return $cache[$table];
    }
  }

  // Return an empty array if there is no match.
  return array();
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
 * Fetch the plugin data from cache.
 */
function _views_fetch_plugin_data($type = NULL, $id = NULL, $reset = FALSE) {
  if (!$type && !$id) {
    $plugins = array();
    $plugin_types = array('access', 'argument', 'argument_default', 'argument_validator', 'cache', 'display_extender', 'display', 'exposed_form', 'localization', 'pager', 'query', 'row', 'style', 'wizard');
    foreach ($plugin_types as $plugin_type) {
      $manager = new ViewsPluginManager($plugin_type);
      $plugins[$plugin_type] = $manager->getDefinitions();
    }
    return $plugins;
  }

  $manager = new ViewsPluginManager($type);

  if (!$id) {
    return $manager->getDefinitions();
  }
  else {
    return $manager->getDefinition($id);
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
  if (config('views.settings')->get('views_skip_cache')) {
    return;
  }
  if ($use_language) {
    $cid .= ':' . language(LANGUAGE_TYPE_INTERFACE)->langcode;
  }

  cache('cache_views')->set($cid, $data);
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
  if (config('views.settings')->get('views_skip_cache')) {
    return FALSE;
  }
  if ($use_language) {
    $cid .= ':' . language(LANGUAGE_TYPE_INTERFACE)->langcode;
  }

  return cache('cache_views')->get($cid);
}
