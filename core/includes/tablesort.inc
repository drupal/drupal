<?php

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * @file
 * Functions to aid in the creation of sortable tables.
 *
 * All tables created when rendering a '#type' => 'table' have the option of
 * having column headers that the user can click on to sort the table by that
 * column.
 */

/**
 * Initializes the table sort context.
 */
function tablesort_init($header) {
  $ts = tablesort_get_order($header);
  $ts['sort'] = tablesort_get_sort($header);
  $ts['query'] = tablesort_get_query_parameters();
  return $ts;
}

/**
 * Formats a column header.
 *
 * If the cell in question is the column header for the current sort criterion,
 * it gets special formatting. All possible sort criteria become links.
 *
 * @param $cell
 *   The cell to format.
 * @param $header
 *   An array of column headers in the format described in '#type' => 'table'.
 * @param $ts
 *   The current table sort context as returned from tablesort_init().
 *
 * @return
 *   A properly formatted cell, ready for _theme_table_cell().
 */
function tablesort_header($cell, $header, $ts) {
  // Special formatting for the currently sorted column header.
  if (is_array($cell) && isset($cell['field'])) {
    $title = t('sort by @s', array('@s' => $cell['data']));
    if ($cell['data'] == $ts['name']) {
      // aria-sort is a WAI-ARIA property that indicates if items in a table
      // or grid are sorted in ascending or descending order. See
      // http://www.w3.org/TR/wai-aria/states_and_properties#aria-sort
      $cell['aria-sort'] = ($ts['sort'] == 'asc') ? 'ascending' : 'descending';
      $ts['sort'] = (($ts['sort'] == 'asc') ? 'desc' : 'asc');
      $cell['class'][] = 'active';
      $tablesort_indicator = array(
        '#theme' => 'tablesort_indicator',
        '#style' => $ts['sort'],
      );
      $image = drupal_render($tablesort_indicator);
    }
    else {
      // If the user clicks a different header, we want to sort ascending initially.
      $ts['sort'] = 'asc';
      $image = '';
    }
    $cell['data'] = l($cell['data'] . $image, current_path(), array('attributes' => array('title' => $title), 'query' => array_merge($ts['query'], array('sort' => $ts['sort'], 'order' => $cell['data'])), 'html' => TRUE));

    unset($cell['field'], $cell['sort']);
  }
  return $cell;
}

/**
 * Formats a table cell.
 *
 * Adds a class attribute to all cells in the currently active column.
 *
 * @param $cell
 *   The cell to format.
 * @param $header
 *   An array of column headers in the format described in '#type' => 'table'.
 * @param $ts
 *   The current table sort context as returned from tablesort_init().
 * @param $i
 *   The index of the cell's table column.
 *
 * @return
 *   A properly formatted cell, ready for _theme_table_cell().
 */
function tablesort_cell($cell, $header, $ts, $i) {
  if (isset($header[$i]['data']) && $header[$i]['data'] == $ts['name'] && !empty($header[$i]['field'])) {
    if (is_array($cell)) {
      $cell['class'][] = 'active';
    }
    else {
      $cell = array('data' => $cell, 'class' => array('active'));
    }
  }
  return $cell;
}

/**
 * Composes a URL query parameter array for table sorting links.
 *
 * @return
 *   A URL query parameter array that consists of all components of the current
 *   page request except for those pertaining to table sorting.
 */
function tablesort_get_query_parameters() {
  return UrlHelper::filterQueryParameters(\Drupal::request()->query->all(), array('sort', 'order'));
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
 */
function tablesort_get_order($headers) {
  $order = \Drupal::request()->query->get('order', '');
  foreach ($headers as $header) {
    if (is_array($header)) {
      if (isset($header['data']) && $order == $header['data']) {
        $default = $header;
        break;
      }

      if (empty($default) && isset($header['sort']) && ($header['sort'] == 'asc' || $header['sort'] == 'desc')) {
        $default = $header;
      }
    }
  }

  if (!isset($default)) {
    $default = reset($headers);
    if (!is_array($default)) {
      $default = array('data' => $default);
    }
  }

  $default += array('data' => NULL, 'field' => NULL);
  return array('name' => $default['data'], 'sql' => $default['field']);
}

/**
 * Determines the current sort direction.
 *
 * @param $headers
 *   An array of column headers in the format described in '#type' => 'table'.
 *
 * @return
 *   The current sort direction ("asc" or "desc").
 */
function tablesort_get_sort($headers) {
  $query = \Drupal::request()->query;
  if ($query->has('sort')) {
    return (strtolower($query->get('sort')) == 'desc') ? 'desc' : 'asc';
  }
  // The user has not specified a sort. Use the default for the currently sorted
  // header if specified; otherwise use "asc".
  else {
    // Find out which header is currently being sorted.
    $ts = tablesort_get_order($headers);
    foreach ($headers as $header) {
      if (is_array($header) && isset($header['data']) && $header['data'] == $ts['name'] && isset($header['sort'])) {
        return $header['sort'];
      }
    }
  }
  return 'asc';
}
