<?php

/**
 * @file
 * Functions to aid in the creation of sortable tables.
 *
 * All tables created when rendering a '#type' => 'table' have the option of
 * having column headers that the user can click on to sort the table by that
 * column.
 */

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;

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
 * @param string $cell_content
 *   The cell content to format. Passed by reference.
 * @param array $cell_attributes
 *   The cell attributes. Passed by reference.
 * @param array $header
 *   An array of column headers in the format described in '#type' => 'table'.
 * @param array $ts
 *   The current table sort context as returned from tablesort_init().
 */
function tablesort_header(&$cell_content, array &$cell_attributes, array $header, array $ts) {
  // Special formatting for the currently sorted column header.
  if (isset($cell_attributes['field'])) {
    $title = t('sort by @s', ['@s' => $cell_content]);
    if ($cell_content == $ts['name']) {
      // aria-sort is a WAI-ARIA property that indicates if items in a table
      // or grid are sorted in ascending or descending order. See
      // http://www.w3.org/TR/wai-aria/states_and_properties#aria-sort
      $cell_attributes['aria-sort'] = ($ts['sort'] == 'asc') ? 'ascending' : 'descending';
      $ts['sort'] = (($ts['sort'] == 'asc') ? 'desc' : 'asc');
      $cell_attributes['class'][] = 'is-active';
      $tablesort_indicator = [
        '#theme' => 'tablesort_indicator',
        '#style' => $ts['sort'],
      ];
      $image = \Drupal::service('renderer')->render($tablesort_indicator);
    }
    else {
      // If the user clicks a different header, we want to sort ascending initially.
      $ts['sort'] = 'asc';
      $image = '';
    }
    $cell_content = \Drupal::l(SafeMarkup::format('@cell_content@image', ['@cell_content' => $cell_content, '@image' => $image]), new Url('<current>', [], [
      'attributes' => ['title' => $title],
      'query' => array_merge($ts['query'], [
        'sort' => $ts['sort'],
        'order' => $cell_content,
      ]),
    ]));

    unset($cell_attributes['field'], $cell_attributes['sort']);
  }
}

/**
 * Composes a URL query parameter array for table sorting links.
 *
 * @return
 *   A URL query parameter array that consists of all components of the current
 *   page request except for those pertaining to table sorting.
 */
function tablesort_get_query_parameters() {
  return UrlHelper::filterQueryParameters(\Drupal::request()->query->all(), ['sort', 'order']);
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
      $default = ['data' => $default];
    }
  }

  $default += ['data' => NULL, 'field' => NULL];
  return ['name' => $default['data'], 'sql' => $default['field']];
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
