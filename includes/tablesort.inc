<?php

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
 * Fetch pager link arguments.
 *
 * When producing a sortable table that presents paged data, pass the output
 * of this function into theme('pager') to preserve the current sort order.
 */
function tablesort_pager() {
  $cgi = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
  unset($cgi['q'], $cgi['from']);
  return $cgi;
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
    $sql = check_query($ts['sql']);
    $sort = strtoupper(check_query($ts['sort']));
    return " ORDER BY $before $sql $sort";
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
  if (is_array($cell) && $cell['field']) {
    if ($cell['data'] == $ts['name']) {
      $ts['sort'] = (($ts['sort'] == 'asc') ? 'desc' : 'asc');
      $cell['class'] = 'active';
      $title = ($ts['sort'] == 'asc' ? t('sort ascending') : t('sort descending'));
      $image = '&nbsp;'. theme('image', 'misc/arrow-'. $ts['sort'] .'.png', t('sort icon'), $title);
    }
    else {
      // If the user clicks a different header, we want to sort ascending initially.
      $ts['sort'] = 'asc';
    }
    $title = t('sort by %s', array('%s' => $cell['data']));
    $cell['data'] = l($cell['data'] . $image, $_GET['q'], array('title' => $title), 'sort='. $ts['sort'] .'&amp;order='. urlencode($cell['data']). $ts['query_string']);

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
  if ($header[$i]['data'] == $ts['name'] && $header[$i]['field']) {
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
  $cgi = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
  foreach ($cgi as $key => $val) {
    if ($key != 'order' && $key != 'sort' && $key != 'q') {
      $query_string .= '&amp;'. $key .'='. $val;
    }
  }
  return $query_string;
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
  $order = $_GET['order'];
  foreach ($headers as $header) {
    if ($order == $header['data']) {
      return array('name' => $header['data'], 'sql' => $header['field']);
    }

    if ($header['sort'] == 'asc' || $header['sort'] == 'desc') {
      $default = array('name' => $header['data'], 'sql' => $header['field']);
    }
  }

  if ($default) {
    return $default;
  }
  else {
    // The first column specified is initial 'order by' field unless otherwise specified
    if (is_array($headers[0])) {
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
  if ($_GET['sort']) {
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

?>
