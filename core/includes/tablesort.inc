<?php

/**
 * @file
 * Functions to aid in the creation of sortable tables.
 *
 * All tables created with a call to theme('table') have the option of having
 * column headers that the user can click on to sort the table by that column.
 */


/**
 * Query extender class for tablesort queries.
 */
class TableSort extends SelectQueryExtender {

  /**
   * The array of fields that can be sorted by.
   *
   * @var array
   */
  protected $header = array();

  public function __construct(SelectQueryInterface $query, DatabaseConnection $connection) {
    parent::__construct($query, $connection);

    // Add convenience tag to mark that this is an extended query. We have to
    // do this in the constructor to ensure that it is set before preExecute()
    // gets called.
    $this->addTag('tablesort');
  }

  /**
   * Order the query based on a header array.
   *
   * @see theme_table()
   * @param $header
   *   Table header array.
   * @return SelectQueryInterface
   *   The called object.
   */
  public function orderByHeader(Array $header) {
    $this->header = $header;
    $ts = $this->init();
    if (!empty($ts['sql'])) {
      // Based on code from db_escape_table(), but this can also contain a dot.
      $field = preg_replace('/[^A-Za-z0-9_.]+/', '', $ts['sql']);

      // Sort order can only be ASC or DESC.
      $sort = drupal_strtoupper($ts['sort']);
      $sort = in_array($sort, array('ASC', 'DESC')) ? $sort : '';
      $this->orderBy($field, $sort);
    }
    return $this;
  }

  /**
   * Initialize the table sort context.
   */
  protected function init() {
    $ts = $this->order();
    $ts['sort'] = $this->getSort();
    $ts['query'] = $this->getQueryParameters();
    return $ts;
  }

  /**
   * Determine the current sort direction.
   *
   * @param $headers
   *   An array of column headers in the format described in theme_table().
   * @return
   *   The current sort direction ("asc" or "desc").
   */
  protected function getSort() {
    return tablesort_get_sort($this->header);
  }

  /**
   * Compose a URL query parameter array to append to table sorting requests.
   *
   * @return
   *   A URL query parameter array that consists of all components of the current
   *   page request except for those pertaining to table sorting.
   *
   * @see tablesort_get_query_parameters()
   */
  protected function getQueryParameters() {
    return tablesort_get_query_parameters();
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
  protected function order() {
    return tablesort_get_order($this->header);
  }
}

/**
 * Initialize the table sort context.
 */
function tablesort_init($header) {
  $ts = tablesort_get_order($header);
  $ts['sort'] = tablesort_get_sort($header);
  $ts['query'] = tablesort_get_query_parameters();
  return $ts;
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
      $cell['class'][] = 'active';
      $image = theme('tablesort_indicator', array('style' => $ts['sort']));
    }
    else {
      // If the user clicks a different header, we want to sort ascending initially.
      $ts['sort'] = 'asc';
      $image = '';
    }
    $cell['data'] = l($cell['data'] . $image, $_GET['q'], array('attributes' => array('title' => $title), 'query' => array_merge($ts['query'], array('sort' => $ts['sort'], 'order' => $cell['data'])), 'html' => TRUE));

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
      $cell['class'][] = 'active';
    }
    else {
      $cell = array('data' => $cell, 'class' => array('active'));
    }
  }
  return $cell;
}

/**
 * Compose a URL query parameter array for table sorting links.
 *
 * @return
 *   A URL query parameter array that consists of all components of the current
 *   page request except for those pertaining to table sorting.
 */
function tablesort_get_query_parameters() {
  return drupal_get_query_parameters($_GET, array('q', 'sort', 'order'));
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
 * Determine the current sort direction.
 *
 * @param $headers
 *   An array of column headers in the format described in theme_table().
 * @return
 *   The current sort direction ("asc" or "desc").
 */
function tablesort_get_sort($headers) {
  if (isset($_GET['sort'])) {
    return (strtolower($_GET['sort']) == 'desc') ? 'desc' : 'asc';
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
