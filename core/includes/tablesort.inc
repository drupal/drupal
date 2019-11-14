<?php

/**
 * @file
 * Functions to aid in the creation of sortable tables.
 *
 * All tables created when rendering a '#type' => 'table' have the option of
 * having column headers that the user can click on to sort the table by that
 * column.
 */

use Drupal\Core\Utility\TableSort;

/**
 * Initializes the table sort context.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Utility\TableSort::getContextFromRequest() instead.
 *
 * @see \Drupal\Core\Utility\TableSortInterface::getContextFromRequest()
 * @see https://www.drupal.org/node/3009182
 */
function tablesort_init($header) {
  @trigger_error(__FUNCTION__ . '() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getContextFromRequest() instead. See https://www.drupal.org/node/3009182', E_USER_DEPRECATED);
  return TableSort::getContextFromRequest($header, \Drupal::request());
}

/**
 * Formats a column header.
 *
 * If the cell in question is the column header for the current sort criterion,
 * it gets special formatting. All possible sort criteria become links.
 *
 * @param string $cell_content
 *   The cell content to format. Passed by reference.
 * @param array $cell_attributes
 *   The cell attributes. Passed by reference.
 * @param array $header
 *   An array of column headers in the format described in '#type' => 'table'.
 * @param array $ts
 *   The current table sort context as returned from tablesort_init().
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Utility\TableSort::header() instead.
 *
 * @see \Drupal\Core\Utility\TableSortInterface::header()
 * @see https://www.drupal.org/node/3009182
 */
function tablesort_header(&$cell_content, array &$cell_attributes, array $header, array $ts) {
  @trigger_error(__FUNCTION__ . '() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::header() instead. See https://www.drupal.org/node/3009182', E_USER_DEPRECATED);
  TableSort::header($cell_content, $cell_attributes, $header, $ts);
}

/**
 * Composes a URL query parameter array for table sorting links.
 *
 * @return
 *   A URL query parameter array that consists of all components of the current
 *   page request except for those pertaining to table sorting.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Utility\TableSort::getQueryParameters() instead.
 *
 * @see \Drupal\Core\Utility\TableSort::getQueryParameters()
 * @see https://www.drupal.org/node/3009182
 */
function tablesort_get_query_parameters() {
  @trigger_error(__FUNCTION__ . '() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getQueryParameters() instead. See https://www.drupal.org/node/3009182', E_USER_DEPRECATED);
  return TableSort::getQueryParameters(\Drupal::request());
}

/**
 * Determines the current sort criterion.
 *
 * @param $headers
 *   An array of column headers in the format described in '#type' => 'table'.
 *
 * @return
 *   An associative array describing the criterion, containing the keys:
 *   - "name": The localized title of the table column.
 *   - "sql": The name of the database field to sort on.
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Utility\TableSort::getOrder() instead.
 *
 * @see \Drupal\Core\Utility\TableSortInterface::getOrder()
 * @see https://www.drupal.org/node/3009182
 */
function tablesort_get_order($headers) {
  @trigger_error(__FUNCTION__ . '() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getOrder() instead. See https://www.drupal.org/node/3009182', E_USER_DEPRECATED);
  return TableSort::getOrder($headers, \Drupal::request());
}

/**
 * Determines the current sort direction.
 *
 * @param $headers
 *   An array of column headers in the format described in '#type' => 'table'.
 *
 * @return
 *   The current sort direction ("asc" or "desc").
 *
 * @deprecated in drupal:8.7.0 and is removed from drupal:9.0.0. Use
 *   \Drupal\Core\Utility\TableSort::getSort() instead.
 *
 * @see \Drupal\Core\Utility\TableSortInterface::getSort()
 * @see https://www.drupal.org/node/3009182
 */
function tablesort_get_sort($headers) {
  @trigger_error(__FUNCTION__ . '() is deprecated in Drupal 8.7.x and will be removed before Drupal 9.0.0. Use \Drupal\Core\Utility\TableSort::getSort() instead. See https://www.drupal.org/node/3009182', E_USER_DEPRECATED);
  return TableSort::getSort($headers, \Drupal::request());
}
