<?php
// $Id$

function tablesort_init($header) {

  $ts["order"] = tablesort_get_order($header);
  $ts["order_sql"] = tablesort_get_order_sql($header, $ts["order"]);
  $ts["sort"] = tablesort_get_sort($header);
  $ts["query_string"] = tablesort_get_querystring();

  return $ts;
}

function tablesort_pager() {
  $cgi = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
  unset($cgi["q"], $cgi["from"]);
  return $cgi;
}

function tablesort_sql($header) {
  $ts = tablesort_init($header);
  return " ORDER BY ". $ts["order_sql"]. " ". strtoupper($ts["sort"]);
}

function tablesort($cell, $header) {
  $ts = tablesort_init($header);
  $title = t("sort by %s", array("%s" => $cell["data"]));

  // special formatting for the currently sorted column header
  if ($cell["data"] == $ts["order"]) {
    $cell["class"] = "cell-highlight";
    $image = "&nbsp;<img border=\"0\" src=\"". theme("image", "arrow-". $ts["sort"]. ".gif"). "\"></img>";
    $dir = array("asc" => "ascending", "desc" => "descending");
    $title = t("sort ". $dir[$ts["sort"]]);
  }

  $cell["data"] = l($cell["data"] . $image, $_GET["q"], array("title" => $title), "sort=". $ts["sort"]. "&order=". urlencode($cell["data"]). $ts["query_string"]);
  return $cell;
}

function tablesort_get_querystring() {
  $cgi = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
  foreach ($cgi as $key => $val) {
    if ($key != "order" && $key != "sort" && $key != "q") {
      $query_string .= "&" . $key . "=" . $val;
    }
  }
  return $query_string;
}

function tablesort_get_order($headers) {
  if ($_GET['order'] != NULL) {
    return $_GET['order'];
  }
  else {
    foreach ($headers as $header) {
      if ($header["sort"] == 'asc' || $header["sort"] == 'desc') {
        return $header["data"];
      }
      elseif (!$first) {
        // the first column specified is initial 'order by' field unless otherwise specified
        $first = $header["data"];
      }
    }
    return $first;
  }
}

function tablesort_get_order_sql($header, $order) {
  foreach ($header as $cell) {
    if ($cell["data"] == $order) {
      return $cell["field"];
    }
  }
}

function tablesort_get_sort($headers) {
  if ($_GET['sort']) {
    return ($_GET['sort'] == 'desc') ? 'asc' : 'desc';
  }
  // user has not specified a sort.  check module for default and if none, use 'asc'
  else {
    foreach ($headers as $header) {
      if (isset($header["sort"])) {
        return $header["sort"];
      }
    }
  }
  return 'asc';
}

?>
