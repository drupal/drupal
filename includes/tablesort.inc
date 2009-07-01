<?php
// $Id$

/**
 * @file
 * Functions to aid in the creation of sortable tables.
 *
 * All tables created with a call to theme('table') have the option of having
 * column headers that the user can click on to sort the table by that column.
 */

/**
 * Initialize the table sort context.
 */
function tablesort_init($header) {
  $ts = tablesort_get_order($header);
  $ts['sort'] = tablesort_get_sort($header);
  $ts['query_string'] = tablesort_get_querystring();
  return $ts;
}

/**
 * Create an SQL sort clause.
 *
 * This function produces the ORDER BY clause to insert in your SQL queries,
 * assuring that the returned database table rows match the sort order chosen
 * by the user.
 *
 * @param $header
 *   An array of column headers in the format described in theme_table().
 * @param $before
 *   An SQL string to insert after ORDER BY and before the table sorting code.
 *   Useful for sorting by important attributes like "sticky" first.
 * @return
 *   An SQL string to append to the end of a query.
 *
 * @ingroup database
 */
function tablesort_sql($header, $before = '') {
  $ts = tablesort_init($header);
  if ($ts['sql']) {
    // Based on code from db_escape_table(), but this can also contain a dot.
    $field = preg_replace('/[^A-Za-z0-9_.]+/', '', $ts['sql']);

    // Sort order can only be ASC or DESC.
    $sort = drupal_strtoupper($ts['sort']);
    $sort = in_array($sort, array('ASC', 'DESC')) ? $sort : '';

    return " ORDER BY $before $field $sort";
  }
}

/**
 * Format a column header.
 *
 * If the cell in question is the column header for the current sort criterion,
 * it gets special formatting. All possible sort criteria become links.
 *
 * @param $cell
 *   The cell to format.
 * @param $header
 *   An array of column headers in the format described in theme_table().
 * @param $ts
 *   The current table sort context as returned from tablesort_init().
 * @return
 *   A properly formatted cell, ready for _theme_table_cell().
 */
function tablesort_header($cell, $header, $ts) {
  // Special formatting for the currently sorted column header.
  if (is_array($cell) && isset($cell['field'])) {
    $title = t('sort by @s', array('@s' => $cell['data']));
    if ($cell['data'] == $ts['name']) {
      $ts['sort'] = (($ts['sort'] == 'asc') ? 'desc' : 'asc');
      if (isset($cell['class'])) {
        $cell['class'] .= ' active';
      }
      else {
        $cell['class'] = 'active';
      }
      $image = theme('tablesort_indicator', $ts['sort']);
    }
    else {
      // If the user clicks a different header, we want to sort ascending initially.
      $ts['sort'] = 'asc';
      $image = '';
    }

    if (!empty($ts['query_string'])) {
      $ts['query_string'] = '&'. $ts['query_string'];
    }
    $cell['data'] = l($cell['data'] . $image, $_GET['q'], array('attributes' => array('title' => $title), 'query' => 'sort='. $ts['sort'] .'&order='. urlencode($cell['data']) . $ts['query_string'], 'html' => TRUE));

    unset($cell['field'], $cell['sort']);
  }
  return $cell;
}

/**
 * Format a table cell.
 *
 * Adds a class attribute to all cells in the currently active column.
 *
 * @param $cell
 *   The cell to format.
 * @param $header
 *   An array of column headers in the format described in theme_table().
 * @param $ts
 *   The current table sort context as returned from tablesort_init().
 * @param $i
 *   The index of the cell's table column.
 * @return
 *   A properly formatted cell, ready for _theme_table_cell().
 */
function tablesort_cell($cell, $header, $ts, $i) {
  if (isset($header[$i]['data']) && $header[$i]['data'] == $ts['name'] && !empty($header[$i]['field'])) {
    if (is_array($cell)) {
      if (isset($cell['class'])) {
        $cell['class'] .= ' active';
      }
      else {
        $cell['class'] = 'active';
      }
    }
    else {
      $cell = array('data' => $cell, 'class' => 'active');
    }
  }
  return $cell;
}

/**
 * Compose a query string to append to table sorting requests.
 *
 * @return
 *   A query string that consists of all components of the current page request
 *   except for those pertaining to table sorting.
 */
function tablesort_get_querystring() {
  return drupal_query_string_encode($_REQUEST, array_merge(array('q', 'sort', 'order', 'pass'), array_keys($_COOKIE)));
}

/**
 * Determine the current sort criterion.
 *
 * @param $headers
 *   An array of column headers in the format described in theme_table().
 * @return
 *   An associative array describing the criterion, containing the keys:
 *   - "name": The localized title of the table column.
 *   - "sql": The name of the database field to sort on.
 */
function tablesort_get_order($headers) {
  $order = isset($_GET['order']) ? $_GET['order'] : '';
  foreach ($headers as $header) {
    if (isset($header['data']) && $order == $header['data']) {
      return array('name' => $header['data'], 'sql' => isset($header['field']) ? $header['field'] : '');
    }

    if (isset($header['sort']) && ($header['sort'] == 'asc' || $header['sort'] == 'desc')) {
      $default = array('name' => $header['data'], 'sql' => isset($header['field']) ? $header['field'] : '');
    }
  }

  if (isset($default)) {
    return $default;
  }
  else {
    // The first column specified is initial 'order by' field unless otherwise specified
    if (is_array($headers[0])) {
      $headers[0] += array('data' => NULL, 'field' => NULL);
      return array('name' => $headers[0]['data'], 'sql' => $headers[0]['field']);
    }
    else {
      return array('name' => $headers[0]);
    }
  }
}

/**
 * Determine the current sort direction.
 *
 * @param $headers
 *   An array of column headers in the format described in theme_table().
 * @return
 *   The current sort direction ("asc" or "desc").
 */
function tablesort_get_sort($headers) {
  if (isset($_GET['sort'])) {
    return ($_GET['sort'] == 'desc') ? 'desc' : 'asc';
  }
  // User has not specified a sort. Use default if specified; otherwise use "asc".
  else {
    foreach ($headers as $header) {
      if (is_array($header) && array_key_exists('sort', $header)) {
        return $header['sort'];
      }
    }
  }
  return 'asc';
}
