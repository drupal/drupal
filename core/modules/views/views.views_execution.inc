<?php

/**
 * @file
 * Provides views runtime hooks for views.module.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 *
 * Substitute current time; this works with cached queries.
 */
function views_views_query_substitutions(ViewExecutable $view) {
  return array(
    '***CURRENT_VERSION***' => VERSION,
    '***CURRENT_TIME***' => REQUEST_TIME,
    '***CURRENT_LANGUAGE***' => language(LANGUAGE_TYPE_CONTENT)->langcode,
    '***DEFAULT_LANGUAGE***' => language_default()->langcode,
  );
}
