<?php

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
 *   also the count query) using printf() syntax.
 * @return
 *   A database query result resource, or FALSE if the query was not executed
 *   correctly.
 *
 * @ingroup database
 */
function pager_query($query, $limit = 10, $element = 0, $count_query = NULL) {
  global $pager_from_array, $pager_total;
  $from = $_GET['from'];

  // Substitute in query arguments.
  $args = func_get_args();
  $args = array_slice($args, 4);

  // Count the total number of records in this query.
  if (!isset($count_query)) {
    $count_query = preg_replace(array('/SELECT.*?FROM/As', '/ORDER BY .*/'), array('SELECT COUNT(*) FROM', ''), $query);
  }
  $pager_total[$element] = db_result(call_user_func_array('db_query', array_merge(array($count_query), $args)));

  // Convert comma-separated $from to an array, used by other functions.
  $pager_from_array = explode(',', $from);

  return call_user_func_array('db_query_range', array_merge(array($query), $args, array((int)$pager_from_array[$element], (int)$limit)));
}

/**
 * Format a query pager.
 *
 * Menu callbacks that display paged query results should call theme('pager') to
 * retrieve a pager control so that users can view other results.
 *
 * @param $tags
 *   An array of labels for the controls in the pager.
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $attributes
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates the query pager.
 *
 * @ingroup themeable
 */
function theme_pager($tags = array(), $limit = 10, $element = 0, $attributes = array()) {
  global $pager_total;
  $output = '';

  if ($pager_total[$element] > $limit) {
    $output .= '<div id="pager" class="container-inline">';
    $output .= '<div>'. pager_first(($tags[0] ? $tags[0] : t('first page')), $limit, $element, $attributes) .'</div>';
    $output .= '<div>'. pager_previous(($tags[1] ? $tags[1] : t('previous page')), $limit, $element, 1, $attributes) .'</div>';
    $output .= '<div>'. pager_list($limit, $element, ($tags[2] ? $tags[2] : 9 ), '', $attributes) .'</div>';
    $output .= '<div>'. pager_next(($tags[3] ? $tags[3] : t('next page')), $limit, $element, 1, $attributes) .'</div>';
    $output .= '<div>'. pager_last(($tags[4] ? $tags[4] : t('last page')), $limit, $element, $attributes) .'</div>';
    $output .= '</div>';

    return $output;
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
 * @param $attributes
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 */
function pager_first($text, $limit, $element = 0, $attributes = array()) {
  global $pager_from_array;

  if ($pager_from_array[$element]) {
    return '<a href="'. pager_link(pager_load_array(0, $element, $pager_from_array), $element, $attributes) .'">'. $text .'</a>';
  }
  else {
    // We are already at the first page, so return nothing.
    return ' ';
  }
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
 * @param $attributes
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 */
function pager_previous($text, $limit, $element = 0, $interval = 1, $attributes = array()) {
  global $pager_from_array;
  $from_new = pager_load_array(((int)$pager_from_array[$element] - ((int)$limit * (int)$interval)), $element, $pager_from_array);
  if ($from_new[$element] < 1) {
    return pager_first($text, $limit, $element, $attributes);
  }
  return '<a href="'. pager_link($from_new, $element, $attributes) .'">'. $text .'</a>';
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
 * @param $attributes
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 */
function pager_next($text, $limit, $element = 0, $interval = 1, $attributes = array()) {
  global $pager_from_array, $pager_total;
  $from_new = pager_load_array(((int)$pager_from_array[$element] + ((int)$limit * (int)$interval)), $element, $pager_from_array);
  if ($from_new[$element] < $pager_total[$element]) {
    return '<a href="'. pager_link($from_new, $element, $attributes) .'">'. $text .'</a>';
  }
  return ' ';
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
 * @param $attributes
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 */
function pager_last($text, $limit, $element = 0, $attributes = array()) {
  global $pager_from_array, $pager_total;

  $last_num = (($pager_total[$element] % $limit) ? ($pager_total[$element] % $limit) : $limit);
  $from_new = pager_load_array(($pager_total[$element] - $last_num), $element, $pager_from_array);
  if ($from_new[$element] < ($pager_from_array[$element] + $limit)) {
    return pager_next($text, $limit, $element, 1, $attributes);
  }
  if (($from_new[$element] > $pager_from_array[$element]) && ($from_new[$element] > 0) && ($from_new[$element] < $pager_total[$element])) {
    return '<a href="'. pager_link($from_new, $element, $attributes) .'">'. $text .'</a>';
  }
  return ' ';
}

/**
 * Format a summary of the current pager position, such as "6 through 10 of 52".
 *
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $format
 *   A printf-style format string for customizing the pager text.
 * @return
 *   An HTML string that generates this piece of the query pager.
 */
function pager_detail($limit, $element = 0, $format = '%d through %d of %d.') {
  global $pager_from_array, $pager_total;
  $output = '';

  if ($pager_total[$element] > (int)$pager_from_array[$element] + 1) {
    $output = sprintf($format, (int)$pager_from_array[$element] + 1, ((int)$pager_from_array[$element] + $limit <= $pager_total[$element] ? (int)$pager_from_array[$element] + $limit : $pager_total[$element]), $pager_total[$element]);
  }

  return $output;
}

/**
 * Format a list of nearby pages with additional query results.
 *
 * @param $limit
 *   The number of query results to display per page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $quantity
 *   The number of pages in the list.
 * @param $text
 *   A string of text to display before the page list.
 * @param $attributes
 *   An associative array of query string parameters to append to the pager links.
 * @return
 *   An HTML string that generates this piece of the query pager.
 */
function pager_list($limit, $element = 0, $quantity = 5, $text = '', $attributes = array()) {
  global $pager_from_array, $pager_total;

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil((int)$quantity / 2);
  // offset adds "offset" second page
  $pager_offset = (int)$pager_from_array[$element] % (int)$limit;
  // current is the page we are currently paged to
  if (($pager_current = (ceil(($pager_from_array[$element] + 1) / $limit))) < 1) {
    $pager_current = 1;
  }
  // first is the first page listed by this pager piece (re quantity)
  $pager_first = (int)$pager_current - (int)$pager_middle + 1;
  // last is the last page listed by this pager piece (re quantity)
  $pager_last = (int)$pager_current + (int)$quantity - (int)$pager_middle;
  // max is the maximum number of pages content can is divided into
  if (!$pager_max = (ceil($pager_total[$element] / $limit))) {
    $pager_max = 1;
  }
  if ((int)$pager_offset) {
    // adjust for offset second page
    $pager_max++;
    $pager_current++;
  }
  // End of marker calculations.

  // Prepare for generation loop.
  $i = (int)$pager_first;
  if ($pager_last > $pager_max) {
    // Adjust "center" if at end of query.
    $i = $i + (int)($pager_max - $pager_last);
    $pager_last = $pager_max;
  }
  if ($i <= 0) {
    // Adjust "center" if at start of query.
    $pager_last = $pager_last + (1 - $i);
    $i = 1;
  }
  // End of generation loop preparation.

  // When there is more than one page, create the pager list.
  if ($i != $pager_max) {
    $output = $text;
    if ($i > 1) {
      $output .= '... ';
    }

    // Now generate the actual pager piece.
    for (; $i <= $pager_last && $i <= $pager_max; $i++) {
      if ($i < $pager_current) {
        $output .= pager_previous($i, $limit, $element, ($pager_current - $i), $attributes) ." ";
      }
      if ($i == $pager_current) {
        $output .= '<strong>'. $i .'</strong> ';
      }
      if ($i > $pager_current) {
        $output .= pager_next($i, $limit, $element, ($i - $pager_current), $attributes) ." ";
      }
    }

    if ($i < $pager_max) {
      $output .= '...';
    }
  }

  return $output;
}
/**
 * @} End of "Pager pieces".
 */

/**
 * Format a link to a specific query result page.
 *
 * @param $from_new
 *   The first result to display on the linked page.
 * @param $element
 *   An optional integer to distinguish between multiple pagers on one page.
 * @param $attributes
 *   An associative array of query string parameters to append to the pager link.
 * @return
 *   An HTML string that generates the link.
 */
function pager_link($from_new, $element, $attributes = array()) {
  $q = $_GET['q'];
  $from = array_key_exists('from', $_GET) ? $_GET['from'] : '';

  foreach($attributes as $key => $value) {
    $query[] = $key .'='. $value;
  }

  $from_new = pager_load_array($from_new[$element], $element, explode(',', $from));
  if (count($attributes)) {
    $url = url($q, 'from='. implode($from_new, ',') .'&amp;'. implode('&amp;', $query));
  }
  else {
    $url = url($q, 'from='. implode($from_new, ','));
  }

  return $url;
}

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

?>
