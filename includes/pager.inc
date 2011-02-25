<?php

/**
 * @defgroup pager Pager interface
 * @{
 *
 * Pager external functions (API).
 */

/**
 * Use this function when doing select queries you wish to be able to page. The
 * pager uses LIMIT-based queries to fetch only the records required to render a
 * certain page. However, it has to learn the total number of records returned
 * by the query to (among others) compute the number of pages (= number of all
 * records / number of records per page). This is done by inserting "COUNT(*)"
 * in the original query, ie. by rewriting the original query, say "SELECT nid,
 * type FROM node WHERE status = '1' ORDER BY static DESC, created DESC" to read
 * "SELECT COUNT(*) FROM node WHERE status = '1' ORDER BY static DESC, created
 * DESC". Rewriting the query is accomplished using a regular expression.
 *
 * Unfortunately, the rewrite rule does not always work as intended for queries
 * that (already) have a "COUNT(*)" or a "GROUP BY" clause, and possibly for
 * other complex queries. In those cases, you can optionally pass a query that
 * will be used to count the records.
 *
 * For example, if you want to page this query: "SELECT COUNT(*), TYPE FROM node
 * GROUP BY TYPE", pager_query() would invoke the wrong query, being: "SELECT
 * COUNT(*) FROM node GROUP BY TYPE". So instead, you should pass "SELECT
 * COUNT(DISTINCT(TYPE)) FROM node" as the optional $count_query parameter.
 *
 * @param $query the SQL query that needs paging
 * @param $limit the number of rows per page
 * @param $element optional attribute to distringuish between multiple pagers
 *   on one page
 * @param $count_query an optional SQL query used to count records when
 *   rewriting the query would fail
 *
 * @return SQL query result
 */
function pager_query($query, $limit = 10, $element = 0, $count_query = "") {
  global $pager_from_array, $pager_total;
  $from = $_GET["from"];

  // count the total number of records in this query:
  if ($count_query == "") {
    $pager_total[$element] = db_result(db_query(preg_replace(array("/SELECT.*FROM/i", "/ORDER BY .*/"), array("SELECT COUNT(*) FROM", ""), $query)));

  }
  else {
    $pager_total[$element] = db_result(db_query($count_query));
  }

  // convert comma separated $from to an array, used by other functions:
  $pager_from_array = explode(",", $from);

  return db_query_range($query, (int)$pager_from_array[$element], (int)$limit);

}
/** @} End of defgroup pager_api */

/**
 * @addtogroup themeable
 * @{
 */

/**
 * When writing themes, you can rewrite this pager function in your theme.
 * You need to call theme("pager", ...) to get a pager.
 */
function theme_pager($tags = "", $limit = 10, $element = 0, $attributes = array()) {
  global $pager_total;
  if ($pager_total[$element] > $limit) {
    $output .= "<div id=\"pager\" class=\"container-inline\">";
    $output .= "<div>". pager_first(($tags[0] ? $tags[0] : t("first page")), $limit, $element, $attributes) ."</div>";
    $output .= "<div>". pager_previous(($tags[1] ? $tags[1] : t("previous page")), $limit, $element, 1, $attributes) ."</div>";
    $output .= "<div>". pager_list($limit, $element, ($tags[2] ? $tags[2] : 9 ), "", $attributes) ."</div>";
    $output .= "<div>". pager_next(($tags[3] ? $tags[3] : t("next page")), $limit, $element, 1, $attributes) ."</div>";
    $output .= "<div>". pager_last(($tags[4] ? $tags[4] : t("last page")), $limit, $element, $attributes) ."</div>";
    $output .= "</div>";

    return $output;
  }
}
/** @} End of addtogroup themeable */

/**
 * @name Pager pieces
 *   Use these pieces to construct your own custom pagers in your theme. Note
 *   that you should NOT modify this file to customize your pager.
 * @{
 */

/**
 * displays a "first-page" link
 *
 * @param $text defines the name (or image) of the link
 * @param $limit how many nodes are displayed per page
 * @param $element distinguish between multiple pagers on one page
 * @param $attributes extra html attributes for \<a href> (eg. title,
 *   onMouseOver, etc.)
 *
 * @return string html of this pager piece
 */
function pager_first($text, $limit, $element = 0, $attributes = array()) {
  global $pager_from_array;

  if ($pager_from_array[$element]) {
    return "<a href=\"". pager_link(pager_load_array(0, $element, $pager_from_array), $element, $attributes) ."\">$text</a>";
  }
  else {
    // we are already at the first page, return nothing
    return " ";
  }
}

/**
 * displays a "previous-page" link
 *
 * @param $text defines the name (or image) of the link
 * @param $limit how many nodes are displayed per page
 * @param $element distinguish between multiple pagers on one page
 * @param $n how many pages we move back (defaults to 1)
 * @param $attributes extra html attributes for \<a href> (eg. title,
 *   onMouseOver, etc.)
 *
 * @return string html of this pager piece
 */
function pager_previous($text, $limit, $element = 0, $n = 1, $attributes = array()) {
  global $pager_from_array;
  $from_new = pager_load_array(((int)$pager_from_array[$element] - ((int)$limit * (int)$n)), $element, $pager_from_array);
  if ($from_new[$element] < 1) {
    return pager_first($text, $limit, $element, $attributes);
  }
  return "<a href=\"". pager_link($from_new, $element, $attributes) ."\">$text</a>";
}

/**
 * displays a "next-page" link
 *
 * @param $text defines the name (or image) of the link
 * @param $limit how many nodes are displayed per page
 * @param $element distinguish between multiple pagers on one page
 * @param $n how many pages we move back (defaults to 1)
 * @param $attributes extra html attributes for \<a href> (eg. title,
 *   onMouseOver, etc.)
 *
 * @return string html of this pager piece
 */
function pager_next($text, $limit, $element = 0, $n = 1, $attributes = array()) {
  global $pager_from_array, $pager_total;
  $from_new = pager_load_array(((int)$pager_from_array[$element] + ((int)$limit * (int)$n)), $element, $pager_from_array);
  if ($from_new[$element] < $pager_total[$element]) {
    return "<a href=\"". pager_link($from_new, $element, $attributes) ."\">$text</a>";
  }
  return " ";
}

/**
 * displays a "last-page" link
 *
 * @param $text defines the name (or image) of the link
 * @param $limit how many nodes are displayed per page
 * @param $element distinguish between multiple pagers on one page
 * @param $attributes extra html attributes for \<a href> (eg. title,
 *   onMouseOver, etc.)
 *
 * @return string html of this pager piece
 */
function pager_last($text, $limit, $element = 0, $attributes = array()) {
  global $pager_from_array, $pager_total;

  $from_new = pager_load_array(($pager_total[$element] - ($pager_total[$element] % $limit)), $element, $pager_from_array);
  if ($from_new[$element] < ($pager_from_array[$element] + $limit)) {
    return pager_next($text, $limit, $element, 1, $attributes);
  }
  if (($from_new[$element] > $pager_from_array[$element]) && ($from_new[$element] > 0) && $from_new[$element] < $pager_total[$element]) {
    return "<a href=\"". pager_link($from_new, $element, $attributes) ."\">$text</a>";
  }
  return " ";
}

/**
 * displays "%d through %d of $d" type detail about the cur page
 *
 * @param $limit how many nodes are displayed per page
 * @param $element distinguish between multiple pagers on one page
 * @param $format allows you to reword the format string
 *
 * @return string html of this pager piece
 */
function pager_detail($limit, $element = 0, $format = "%d through %d of %d.") {
  global $pager_from_array, $pager_total;

  if ($pager_total[$element] > (int)$pager_from_array[$element] + 1) {
    $output = sprintf($format, (int)$pager_from_array[$element] + 1, ((int)$pager_from_array[$element] + $limit <= $pager_total[$element] ? (int)$pager_from_array[$element] + $limit : $pager_total[$element]), $pager_total[$element]);
  }

  return $output;
}

/**
 * displays a list of nearby pages with additional nodes
 *
 * @param $limit how many nodes are displayed per page
 * @param $element distinguish between multiple pagers on one page
 * @param $quantity defines the length of the page list
 * @param $text optional text to display before the page list
 * @param $attributes extra html attributes for \<a href> (eg. title,
 *   onMouseOver, etc.)
 *
 * @return string html of this pager piece
 */
function pager_list($limit, $element = 0, $quantity = 5, $text = "", $attributes = array()) {
  global $pager_from_array, $pager_total;

  // calculate various markers within this pager piece:
  // middle used to "center" pages around current page
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
  // max is the maximum number of pages content can is devided into
  if (!$pager_max = (ceil($pager_total[$element] / $limit))) {
    $pager_max = 1;
  }
  if ((int)$pager_offset) {
    // adjust for offset second page
    $pager_max++;
    $pager_current++;
  }
// end of various marker calculations

// prepare for generation loop
  $i = (int)$pager_first;
  if ($pager_last > $pager_max) {
    // adjust "center" if at end of query
    $i = $i + (int)($pager_max - $pager_last);
    $pager_last = $pager_max;
  }
  if ($i <= 0) {
    // adjust "center" if at start of query
    $pager_last = $pager_last + (1 - $i);
    $i = 1;
  }
// end of generation loop preparation

  // when there is more than one page, create the pager list
  if ($i != $pager_max) {
    $output = "$text";
    if ($i > 1) {
      $output .= "... ";
    }

    // finally we're ready to generate the actual pager piece
    for (; $i <= $pager_last && $i <= $pager_max; $i++) {
      if ($i < $pager_current) {
        $output .= pager_previous($i, $limit, $element, ($pager_current - $i), $attributes) ." ";
      }
      if ($i == $pager_current) {
        $output .= "<strong>$i</strong> ";
      }
      if ($i > $pager_current) {
        $output .= pager_next($i, $limit, $element, ($i - $pager_current), $attributes) ." ";
      }
    }

    if ($i < $pager_max) {
      $output .= "...";
    }
  }

  return $output;
}
/* @} End of member group pager pieces */

function pager_link($from_new, $element, $attributes = array()) {
  $q = $_GET["q"];
  $from = $_GET["from"];

  foreach($attributes as $key => $value) {
    $query[] = "$key=$value";
  }

  $from_new = pager_load_array($from_new[$element], $element, explode("," ,$from));
  if (count($attributes)) {
    $url = url($q, "from=". implode($from_new, ",") ."&amp;". implode("&amp;", $query));
  }
  else {
    $url = url($q, "from=". implode($from_new, ","));
  }

  return $url;
}

function pager_load_array($value, $element, $old_array) {
  $new_array = $old_array;
  // look for empty elements
  for ($i = 0; $i < $element; $i++) {
    if (!$new_array[$i]) {
      // load found empty element with 0
      $new_array[$i] = 0;
    }
  }
  // update the changed element
  $new_array[$element] = (int)$value;
  return $new_array;
}

?>
