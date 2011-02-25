<?php

function tablesort_init($header) {
  static $ts;

  if (empty($ts)) {
    $ts['order'] = tablesort_get_order($header);
    $ts['order_sql'] = tablesort_get_order_sql($header, $ts['order']);
    $ts['sort'] = tablesort_get_sort($header);
    $ts['query_string'] = tablesort_get_querystring();
  }

  return $ts;
}

function tablesort_pager() {
  $cgi = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
  unset($cgi['q'], $cgi["from"]);
  return $cgi;
}

function tablesort_sql($header) {
  $ts = tablesort_init($header);
  return " ORDER BY ". $ts['order_sql']. " ". strtoupper($ts['sort']);
}

function tablesort($cell, $header) {
  $ts = tablesort_init($header);
  $title = t("sort by %s", array("%s" => $cell['data']));

  // special formatting for the currently sorted column header
  if ($cell['data'] == $ts['order']) {
    $ts['sort'] = (($ts['sort'] == 'asc') ? 'desc' : 'asc');
    $cell['class'] = 'cell-highlight';
    $image = '&nbsp;<img src="' . theme('image', 'arrow-' . $ts['sort'] . '.gif') . '" alt="'. t('sort icon') .'" />';
    $title = ($ts['sort'] == 'asc' ? t("sort ascending") : t("sort descending"));
  } else {
    // If the user clicks a different header, we want to sort ascending initially.
    $ts['sort'] = "asc";
  }

  $cell['data'] = l($cell['data'] . $image, $_GET['q'], array("title" => $title), "sort=". $ts['sort']. "&amp;order=". urlencode($cell['data']). $ts['query_string']);

  unset($cell['field']);
  unset($cell['sort']);

  return $cell;
}

function tablesort_get_querystring() {
  $cgi = $_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST;
  foreach ($cgi as $key => $val) {
    if ($key != 'order' && $key != 'sort' && $key != 'q') {
      $query_string .= "&amp;". $key ."=". $val;
    }
  }
  return $query_string;
}

function tablesort_get_order($headers) {
  $order = $_GET['order'];
  foreach ($headers as $header) {
    if ($order == $header['data']) {
      return $header['data'];
    }

    if ($header['sort'] == 'asc' || $header['sort'] == 'desc') {
      $default = $header['data'];
    }
  }

  if ($default) {
    return $default;
  }
  else {
    // The first column specified is initial 'order by' field unless otherwise specified
    $first = reset($headers);
    return $first['data'];
  }
}

function tablesort_get_order_sql($header, $order) {
  foreach ($header as $cell) {
    if ($cell['data'] == $order) {
      return $cell['field'];
    }
  }
}

function tablesort_get_sort($headers) {
  if ($_GET['sort']) {
    return ($_GET['sort'] == 'desc') ? 'desc' : 'asc';
  }
  // user has not specified a sort.  check module for default and if none, use 'asc'
  else {
    foreach ($headers as $header) {
      if (is_array($header) && isset($header['sort'])) {
        return $header['sort'];
      }
    }
  }
  return 'asc';
}

?>
