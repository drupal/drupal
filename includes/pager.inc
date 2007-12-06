<?php
// $Id: pager.inc,v 1.63 2007/12/06 09:58:30 goba Exp $

/**
 * @file
 * Functions to aid in presenting database results as a set of pages.
 */

/**
 * Perform a paged database query.
 *
 * Use this function when doing select queries you wish to be able to page. The
 * pager uses LIMIT-based queries to fetch only the records required to render a
 * certain page. However, it has to learn the total number of records returned
 * by the query to compute the number of pages (the number of records / records
 * per page). This is done by inserting "COUNT(*)" in the original query. For
 * example, the query "SELECT nid, type FROM node WHERE status = '1' ORDER BY
 * sticky DESC, created DESC" would be rewritten to read "SELECT COUNT(*) FROM
 * node WHERE status = '1' ORDER BY sticky DESC, created DESC". Rewriting the
 * query is accomplished using a regular expression.
 *
 * Unfortunately, the rewrite rule does not always work as intended for queries
 * that already have a "COUNT(*)" or a "GROUP BY" clause, and possibly for
 * other complex queries. In those cases, you can optionally pass a query that
 * will be used to count the records.
 *
 * For example, if you want to page the query "SELECT COUNT(*), TYPE FROM node
 * GROUP BY TYPE", pager_query() would invoke the incorrect query "SELECT
 * COUNT(*) FROM node GROUP BY TYPE". So instead, you should pass "SELECT
 * COUNT(DISTINCT(TYPE)) FROM node" as the optional $count_query parameter.
 *
 * @param $query
 *   The SQL query that needs paging.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $count_query
 *   An SQL query used to count matching records.
 * @param ...
 *   A variable number of arguments which are substituted into the query (and
 *   the count query) using printf() syntax. Instead of a variable number of
 *   query arguments, you may also pass a single array containing the query
 *   arguments.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 *
 * @ingroup database
 */
function pager_query($query, $limit = 10, $element = 0, $count_query = NULL) {
  global $pager_page_array, $pager_total, $pager_total_items;
  $page = isset($_GET['page']) ? $_GET['page'] : '';

  // Substitute in query arguments.
  $args = func_get_args();
  $args = array_slice($args, 4);
  // Alternative syntax for '...'
  if (isset($args[0]) && is_array($args[0])) {
    $args = $args[0];
  }

  // Construct a count query if none was given.
  if (!isset($count_query)) {
    $count_query = preg_replace(array('/SELECT.*?FROM /As', '/ORDER BY .*/'), array('SELECT COUNT(*) FROM ', ''), $query);
  }

  // Convert comma-separated $page to an array, used by other functions.
  $pager_page_array = explode(',', $page);

  // We calculate the total of pages as ceil(items / limit).
  $pager_total_items[$element] = db_result(db_query($count_query, $args));
  $pager_total[$element] = ceil($pager_total_items[$element] / $limit);
  $pager_page_array[$element] = max(0, min((int)$pager_page_array[$element], ((int)$pager_total[$element]) - 1));
  return db_query_range($query, $args, $pager_page_array[$element] * $limit, $limit);
}

/**
 * Compose a query string to append to pager requests.
 *
 * @return
 *   A query string that consists of all components of the current page request
 *   except for those pertaining to paging.
 */
function pager_get_querystring() {
  static $string = NULL;
  if (!isset($string)) {
    $string = drupal_query_string_encode($_REQUEST, array_merge(array('q', 'page'), array_keys($_COOKIE)));
  }
  return $string;
}

/**
 * Format a query pager.
 *
 * Menu callbacks that display paged query results should call theme('pager') to
 * retrieve a pager control so that users can view other results.
 * Format a list of nearby pages with additional query results.
 *
 * @param $tags
 *   An array of labels for the controls in the pager.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $parameters
 *   An associative array of query string parameters to append to the pager links.
 * @param $quantity
 *   The number of pages in the list.
 * @return
 *   An HTML string that generates the query pager.
 *
 * @ingroup themeable
 */
function theme_pager($tags = array(), $limit = 10, $element = 0, $parameters = array(), $quantity = 9) {
  global $pager_page_array, $pager_total;

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  // current is the page we are currently paged to
  $pager_current = $pager_page_array[$element] + 1;
  // first is the first page listed by this pager piece (re quantity)
  $pager_first = $pager_current - $pager_middle + 1;
  // last is the last page listed by this pager piece (re quantity)
  $pager_last = $pager_current + $quantity - $pager_middle;
  // max is the maximum page number
  $pager_max = $pager_total[$element];
  // End of marker calculations.

  // Prepare for generation loop.
  $i = $pager_first;
  if ($pager_last > $pager_max) {
    // Adjust "center" if at end of query.
    $i = $i + ($pager_max - $pager_last);
    $pager_last = $pager_max;
  }
  if ($i <= 0) {
    // Adjust "center" if at start of query.
    $pager_last = $pager_last + (1 - $i);
    $i = 1;
  }
  // End of generation loop preparation.

  $li_first = theme('pager_first', (isset($tags[0]) ? $tags[0] : t('« first')), $limit, $element, $parameters);
  $li_previous = theme('pager_previous', (isset($tags[1]) ? $tags[1] : t('‹ previous')), $limit, $element, 1, $parameters);
  $li_next = theme('pager_next', (isset($tags[3]) ? $tags[3] : t('next ›')), $limit, $element, 1, $parameters);
  $li_last = theme('pager_last', (isset($tags[4]) ? $tags[4] : t('last »')), $limit, $element, $parameters);

  if ($pager_total[$element] > 1) {
    if ($li_first) {
      $items[] = array(
        'class' => 'pager-first',
        'data' => $li_first,
      );
    }
    if ($li_previous) {
      $items[] = array(
        'class' => 'pager-previous',
        'data' => $li_previous,
      );
    }

    // When there is more than one page, create the pager list.
    if ($i != $pager_max) {
      if ($i > 1) {
        $items[] = array(
          'class' => 'pager-ellipsis',
          'data' => '…',
        );
      }
      // Now generate the actual pager piece.
      for (; $i <= $pager_last && $i <= $pager_max; $i++) {
        if ($i < $pager_current) {
          $items[] = array(
            'class' => 'pager-item',
            'data' => theme('pager_previous', $i, $limit, $element, ($pager_current - $i), $parameters),
          );
        }
        if ($i == $pager_current) {
          $items[] = array(
            'class' => 'pager-current',
            'data' => $i,
          );
        }
        if ($i > $pager_current) {
          $items[] = array(
            'class' => 'pager-item',
            'data' => theme('pager_next', $i, $limit, $element, ($i - $pager_current), $parameters),
          );
        }
      }
      if ($i < $pager_max) {
        $items[] = array(
          'class' => 'pager-ellipsis',
          'data' => '…',
        );
      }
    }
    // End generation.
    if ($li_next) {
      $items[] = array(
        'class' => 'pager-next',
        'data' => $li_next,
      );
    }
    if ($li_last) {
      $items[] = array(
        'class' => 'pager-last',
        'data' => $li_last,
      );
    }
    return theme('item_list', $items, NULL, 'ul', array('class' => 'pager'));
  }
}


/**
 * @name Pager pieces
 * @{
 * Use these pieces to construct your own custom pagers in your theme. Note that
 * you should NOT modify this file to customize your pager.
 */

/**
 * Format a "first page" link.
 *
 * @param $text
 *   The name (or image) of the link.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $parameters
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 *
 * @ingroup themeable
 */
function theme_pager_first($text, $limit, $element = 0, $parameters = array()) {
  global $pager_page_array;
  $output = '';

  // If we are anywhere but the first page
  if ($pager_page_array[$element] > 0) {
    $output = theme('pager_link', $text, pager_load_array(0, $element, $pager_page_array), $element, $parameters);
  }

  return $output;
}

/**
 * Format a "previous page" link.
 *
 * @param $text
 *   The name (or image) of the link.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $interval
 *   The number of pages to move backward when the link is clicked.
 * @param $parameters
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 *
 * @ingroup themeable
 */
function theme_pager_previous($text, $limit, $element = 0, $interval = 1, $parameters = array()) {
  global $pager_page_array;
  $output = '';

  // If we are anywhere but the first page
  if ($pager_page_array[$element] > 0) {
    $page_new = pager_load_array($pager_page_array[$element] - $interval, $element, $pager_page_array);

    // If the previous page is the first page, mark the link as such.
    if ($page_new[$element] == 0) {
      $output = theme('pager_first', $text, $limit, $element, $parameters);
    }
    // The previous page is not the first page.
    else {
      $output = theme('pager_link', $text, $page_new, $element, $parameters);
    }
  }

  return $output;
}

/**
 * Format a "next page" link.
 *
 * @param $text
 *   The name (or image) of the link.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $interval
 *   The number of pages to move forward when the link is clicked.
 * @param $parameters
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 *
 * @ingroup themeable
 */
function theme_pager_next($text, $limit, $element = 0, $interval = 1, $parameters = array()) {
  global $pager_page_array, $pager_total;
  $output = '';

  // If we are anywhere but the last page
  if ($pager_page_array[$element] < ($pager_total[$element] - 1)) {
    $page_new = pager_load_array($pager_page_array[$element] + $interval, $element, $pager_page_array);
    // If the next page is the last page, mark the link as such.
    if ($page_new[$element] == ($pager_total[$element] - 1)) {
      $output = theme('pager_last', $text, $limit, $element, $parameters);
    }
    // The next page is not the last page.
    else {
      $output = theme('pager_link', $text, $page_new, $element, $parameters);
    }
  }

  return $output;
}

/**
 * Format a "last page" link.
 *
 * @param $text
 *   The name (or image) of the link.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $parameters
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 *
 * @ingroup themeable
 */
function theme_pager_last($text, $limit, $element = 0, $parameters = array()) {
  global $pager_page_array, $pager_total;
  $output = '';

  // If we are anywhere but the last page
  if ($pager_page_array[$element] < ($pager_total[$element] - 1)) {
    $output = theme('pager_link', $text, pager_load_array($pager_total[$element] - 1, $element, $pager_page_array), $element, $parameters);
  }

  return $output;
}


/**
 * Format a link to a specific query result page.
 *
 * @param $page_new
 *   The first result to display on the linked page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $parameters
 *   An associative array of query string parameters to append to the pager link.
 * @param $attributes
 *   An associative array of HTML attributes to apply to a pager anchor tag.
 * @return
 *   An HTML string that generates the link.
 *
 * @ingroup themeable
 */
function theme_pager_link($text, $page_new, $element, $parameters = array(), $attributes = array()) {
  $page = isset($_GET['page']) ? $_GET['page'] : '';
  if ($new_page = implode(',', pager_load_array($page_new[$element], $element, explode(',', $page)))) {
    $parameters['page'] = $new_page;
  }

  $query = array();
  if (count($parameters)) {
    $query[] = drupal_query_string_encode($parameters, array());
  }
  $querystring = pager_get_querystring();
  if ($querystring != '') {
    $query[] = $querystring;
  }

  // Set each pager link title
  if (!isset($attributes['title'])) {
    static $titles = NULL;
    if (!isset($titles)) {
      $titles = array(
        t('« first') => t('Go to first page'),
        t('‹ previous') => t('Go to previous page'),
        t('next ›') => t('Go to next page'),
        t('last »') => t('Go to last page'),
      );
    }
    if (isset($titles[$text])) {
      $attributes['title'] = $titles[$text];
    }
    else if (is_numeric($text)) {
      $attributes['title'] = t('Go to page @number', array('@number' => $text));
    }
  }

  return l($text, $_GET['q'], array('attributes' => $attributes, 'query' => count($query) ? implode('&', $query) : NULL));
}

/**
 * @} End of "Pager pieces".
 */

/**
 * Helper function
 *
 * Copies $old_array to $new_array and sets $new_array[$element] = $value
 * Fills in $new_array[0 .. $element - 1] = 0
 */
function pager_load_array($value, $element, $old_array) {
  $new_array = $old_array;
  // Look for empty elements.
  for ($i = 0; $i < $element; $i++) {
    if (!$new_array[$i]) {
      // Load found empty element with 0.
      $new_array[$i] = 0;
    }
  }
  // Update the changed element.
  $new_array[$element] = (int)$value;
  return $new_array;
}
