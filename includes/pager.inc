<?php

function pager_help() {
  ?>
  <h3>Implementation note: making queries pagable</h3>
  <p>The pager uses <code>LIMIT</code>-based queries to fetch only the records required to render a certain page.  However, it has to learn the total number of records returned by the query to (among others) compute the number of pages (= number of all records / number of records per page). This is done by inserting <code>COUNT(*)</code> in the original query, ie. by rewriting the original query <pre>SELECT nid, type FROM node WHERE status = '1' ORDER BY static DESC, created DESC</pre> to read <pre>SELECT COUNT(*) FROM node WHERE status = '1' ORDER BY static DESC, created DESC</pre>Rewriting the query is accomplished using a regular expression; <code>preg_replace("/SELECT.*FROM/i", "SELECT COUNT(*) FROM", $query)</code>.</p>
  <p>Unfortunately, the call to <code>preg_replace()</code> does not work as intended for queries that already have a <code>COUNT()</code> clause; the original <code>COUNT()</code> will be removed from the query, possibly making the remainder of the query fail (eg. when the use of <code>HAVING</code> or <code>ORDER</code> depends on the value returned by <code>COUNT()</code>).  In practice, for queries to be <code>db_query_pager()</code>-able, they shold be reformulated not to use <code>COUNT()</code>.</p>
 <?php
}

/* ***************************************************
 *            external functions (API)
 * ***************************************************/

/**
 * Use this function in your module or theme to display a pager.
 *
 * @param   array   $tags     defines your buttons; text or img.
 * @param   int     $limit    how many nodes are displayed per page
 * @param   int     $element  support for multiple pagers per page (specify which this is)
 * @param   string  $type     allows for distinction between pagers on main page and admin page, etc.
 *   Supported types are "default", "admin" and "simple".
 *
 * @return  string  html of pager
 */
function pager_display($tags = "", $limit = 10, $element = 0, $type = "default") {
  return theme_invoke("pager_display_". $type, $tags, $limit, $element);
}

/**
 * DEFAULT PAGER:
 *  When writing themes, you can rewrite this pager function in your
 *  theme.  This is the most common pager type, and thus the main one
 *  to re-write in your theme.
 *
 * @see pager_display
 */
function pager_display_default($tags = "", $limit = 10, $element = 0) {
  $output .= "<center><table cellpadding=\"10\"><tbody><tr>";
  $output .= "<td align=\"center\">". pager_first(($tags[0] ? $tags[0] : t("first page")), $limit, $element) ."</td>";
  $output .= "<td align=\"center\">". pager_previous(($tags[1] ? $tags[1] : t("previous page")), $limit, $element) ."</td>";
  $output .= "<td align=\"center\">". pager_list($limit, $element, ($tags[2] ? $tags[2] : 9 )) ."</td>";
  $output .= "<td align=\"center\">". pager_next(($tags[3] ? $tags[3] : t("next page")), $limit, $element) ."</td>";
  $output .= "<td align=\"center\">". pager_last(($tags[4] ? $tags[4] : t("last page")), $limit, $element) ."</td>";
  $output .= "</tr></tbody></table></center>";

  return "$output";
}

/**
 * SIMPLE PAGER:
 *   When writing themes, you can rewrite this pager function in your
 *   theme. Keep in mind that the pager it defines is intended to have
 *   a "simple" look, possibly located in a table or block.
 *
 * @see pager_display
 */
function pager_display_simple($tags = "", $limit = 10, $element = 0) {
  /*
  ** It's left as an exercise to theme writers to create an alternative
  ** pager for pager_display_simple().  if your theme does not offer a
  ** replacement, the theme.inc pager_display_default() is used.
  */
  return pager_display_default($tags, $limit, $element);
}

/**
 * ADMIN PAGER:
 *   When writing themes, you can rewrite this pager function in your
 *   theme.  Most themes will probably NOT re-write this function, as
 *   admin pages are not normally themed.
 *
 * @see pager_display
 */
function pager_display_admin($tags = "", $limit = 10, $element = 0) {
  /*
  ** It's left as an exercise to theme writers to create an alternative
  ** pager for pager_display_admin().  if your theme does not offer a
  ** replacement, the pager.inc pager_display_default() is used.
  */
  return pager_display_default($tags, $limit, $element);
}

/* *******************************************************************
 * PAGER PIECES:
 *  Use these pieces to construct your own custom pagers (i.e. in
 *  themes).  Note that you should NOT modify this file to customize
 *  your pager)
 * *******************************************************************/

/**
 * displays a "first-page" link
 *
 * @see pager_previous
 */
function pager_first($text, $limit, $element = 0) {
  global $from_array;

  if ($from_array[$element]) {
    return "<a href=\"". pager_link(pager_load_array(0, $element, $from_array)) ."\">$text</a>";
  }
  else {
    // we are already at the first page, return nothing
    return " ";
  }
}

/**
 * displays a "previous-page" link
 *
 * @param   string  $text     defines the name (or image) of the link
 * @param   int     $limit    how many nodes are displayed per page
 * @param   int     $element  distinguish between multiple pagers on one page
 * @param   int     $n        how many pages we move back (defaults to 1)
 *
 * @return  string  html of this pager piece
 */
function pager_previous($text, $limit, $element = 0, $n = 1) {
  global $from_array;
  $from_new = pager_load_array(((int)$from_array[$element] - ((int)$limit * (int)$n)), $element, $from_array);
  if ($from_new[$element] < 1) {
    return pager_first($text, $limit, $element);
  }
  return "<a href=\"". pager_link($from_new) ."\">$text</a>";
}

/**
 * displays a "next-page" link
 *
 * @see pager_previous
 */
function pager_next($text, $limit, $element = 0, $n = 1) {
  global $from_array, $pager_total;
  $from_new = pager_load_array(((int)$from_array[$element] + ((int)$limit * (int)$n)), $element, $from_array);
  if ($from_new[$element] < $pager_total[$element]) {
    return "<a href=\"". pager_link($from_new) ."\">$text</a>";
  }
  return " ";
}

/**
 * displays a "last-page" link
 *
 * @see pager_previous
 */
function pager_last($text, $limit, $element = 0) {
  global $from_array, $pager_total;

  $from_new = pager_load_array(($pager_total[$element] - $limit), $element, $from_array);
  if ($from_new[$element] < ($from_array[$element] + $limit)) {
    return pager_next($text, $limit, $element);
  }
  if (($from_new[$element] > $from_array[$element]) && ($from_new[$element] > 0) && $from_new[$element] < $pager_total[$element]) {
    return "<a href=\"". pager_link($from_new) ."\">$text</a>";
  }
  return " ";
}

/**
 * displays "%d through %d of $d" type detail about the cur page
 *
 * @param   string  $format   allows you to reword the format string
 * @see pager_previous
 */
function pager_detail($limit, $element = 0, $format = "%d through %d of %d.") {
  global $from_array, $pager_total;

  if ($pager_total[$element] > (int)$from_array[$element] + 1) {
    $output = sprintf($format, (int)$from_array[$element] + 1, ((int)$from_array[$element] + $limit <= $pager_total[$element] ? (int)$from_array[$element] + $limit : $pager_total[$element]), $pager_total[$element]);
  }

  return $output;
}

/**
 * displays a list of nearby pages with additional nodes
 *
 * @param   int     $quantity   defines the length of the page list
 * @param   string  $text       optional text to display before the page list
 * @see pager_previous
 */
function pager_list($limit, $element = 0, $quantity = 5, $text = "") {
  global $from_array, $pager_total;

  // calculate various markers within this pager piece:
  // middle used to "center" pages around current page
  $pager_middle = ceil((int)$quantity / 2);
  // offset adds "offset" second page
  $pager_offset = (int)$from_array[$element] % (int)$limit;
  // current is the page we are currently paged to
  if (($pager_current = (ceil(($from_array[$element] + 1) / $limit))) < 1) {
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
        $output .= pager_previous($i, $limit, $element, ($pager_current - $i)) ." ";
      }
      if ($i == $pager_current) {
        $output .= "<b>$i</b> ";
      }
      if ($i > $pager_current) {
        $output .= pager_next($i, $limit, $element, ($i - $pager_current)) ." ";
      }
    }

    if ($i < $pager_max) {
      $output .= "...";
    }
  }

  return $output;
}


/* ********************************************************************
 * QUERIES - call this instead of db_query() if you want your query to
 * support a pager.
 * ********************************************************************/

/**
 * Use this function when doing select queries you wish to be able to page.
 *
 * TODO:
 * - remove database dependency ($db_type) piece
 *   . use db_query_range from
 *   . rename db_query_pager() to pager_query()
 * - examine a better solution for the "no COUNT in $query" requirement (see (output of) {@link pager_help()})
 *
 * @param   string  $query    the database query *without* "LIMIT" in it. examples:<pre>
 *   "SELECT * FROM table"
 *   "SELECT field1,field2 FROM table WHERE nid = '1'"</pre>
 * @param   int     $limit    how many rows to return (per page)
 * @param   int     $element  adds support for multiple paged tables on one page
 *
 * @return  resource  MySQL query result
 */
function db_query_pager($query, $limit = 10, $element = 0) {
  global $from, $from_array, $db_type, $pager_total;

  // count the total number of records in this query:
  $array = db_fetch_array(db_query(preg_replace("/SELECT.*FROM/i", "SELECT COUNT(*) FROM", $query)));
  if ($array) {
    $pager_total[$element] = array_pop($array);
  }
  else {
    $pager_total[$element] = 0;
  }

  // convert comma separated $from to an array, used by other functions:
  $from_array = explode(",", $from);

  if ((int)$from_array[$element]) {
    if ($db_type == "mysql") {
      // MySQL formatted limit query with offset:
      $limit_query = $query . " LIMIT " . (int)$from_array[$element] . ", $limit";
    }
    else {
      // pear formatted limit query with offset:
      $limit_query = $query . " LIMIT $limit OFFSET " . (int)$from_array[$element];
    }
  }
  else {
    // standard limit query without offset:
    $limit_query = $query . " LIMIT $limit";
  }
  return db_query($limit_query);
}

function pager_link($from_new) {
  $from_list = @implode(",", $from_new);
  if ("$from_list" == "0") {
    // single pager at zero, so remove the $from
    return preg_replace(array("/from=*[^&]*/", "/[&]$/", "/[?]$/"), "", request_uri());
  }
  if (preg_match("/from=/", request_uri())) {
    // replace existing from=
    return preg_replace("/from=*[^&]*/", "from=$from_list", request_uri());
  }
  if (preg_match("/[?]/", request_uri())) {
    // append &from=
    $href = request_uri() . "&from=$from_list";
  }
  else {
    // append ?from=
    $href = request_uri() . "?from=$from_list";
  }
  return $href;
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
