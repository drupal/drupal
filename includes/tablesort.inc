<?php

function tablesort_init($header) {
  static $ts;

  if (!$ts) {
    $ts["order"] = tablesort_get_order($header);
    $ts["order_sql"] = tablesort_get_order_sql($header, $ts["order"]);
    $ts["sort"] = tablesort_get_sort($header);
    $ts["query_string"] = tablesort_get_querystring();
  }
  return $ts;
}

function tablesort_pager() {
  return array ("order" => $_GET['order'], "sort" => $_GET['sort']);
}

function tablesort_sql($header) {
  $ts = tablesort_init($header);
  return " ORDER BY ". $ts["order_sql"]. " ". strtoupper($ts["sort"]);
}

function tablesort($cell, $header) {
  global $theme;

  $ts = tablesort_init($header);

  // special formatting for the currently sorted column header
  if ($cell["data"] == $ts["order"]) {
    $cell["class"] = "cell-highlight";
    $image = "&nbsp;<img src=\"". $theme->image("arrow-". $ts["sort"]. ".gif"). "\"></img>";
  }
  $cell["data"] = l($cell["data"], $_GET["q"], array(), "sort=". $ts["sort"]. "&order=". urlencode($cell["data"]). $ts["query_string"]). $image;
  return $cell;
}

function tablesort_get_querystring() {
  $cgi = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
  // reset ($cgi);
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
