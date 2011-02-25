<?php

/**
 * @file
 * User page callbacks for the statistics module.
 */

function statistics_node_tracker() {
  if ($node = node_load(arg(1))) {

    $header = array(
        array('data' => t('Time'), 'field' => 'a.timestamp', 'sort' => 'desc'),
        array('data' => t('Referrer'), 'field' => 'a.url'),
        array('data' => t('User'), 'field' => 'u.name'),
        array('data' => t('Operations')));

    $result = pager_query("SELECT a.aid, a.timestamp, a.url, a.uid, u.name FROM {accesslog} a LEFT JOIN {users} u ON a.uid = u.uid WHERE a.path = 'node/%d' OR a.path LIKE 'node/%d/%%'". tablesort_sql($header), 30, 0, NULL, $node->nid, $node->nid);
    $rows = array();
    while ($log = db_fetch_object($result)) {
      $rows[] = array(
        array('data' => format_date($log->timestamp, 'small'), 'class' => 'nowrap'),
        _statistics_link($log->url),
        theme('username', $log),
        l(t('details'), "admin/reports/access/$log->aid"));
    }

    if (empty($rows)) {
      $rows[] = array(array('data' => t('No statistics available.'), 'colspan' => 4));
    }

    drupal_set_title(check_plain($node->title));
    $output = theme('table', $header, $rows);
    $output .= theme('pager', NULL, 30, 0);
    return $output;
  }
  else {
    drupal_not_found();
  }
}

function statistics_user_tracker() {
  if ($account = user_load(array('uid' => arg(1)))) {

    $header = array(
        array('data' => t('Timestamp'), 'field' => 'timestamp', 'sort' => 'desc'),
        array('data' => t('Page'), 'field' => 'path'),
        array('data' => t('Operations')));

    $result = pager_query('SELECT aid, timestamp, path, title FROM {accesslog} WHERE uid = %d'. tablesort_sql($header), 30, 0, NULL, $account->uid);
    $rows = array();
    while ($log = db_fetch_object($result)) {
      $rows[] = array(
        array('data' => format_date($log->timestamp, 'small'), 'class' => 'nowrap'),
        _statistics_format_item($log->title, $log->path),
        l(t('details'), "admin/reports/access/$log->aid"));
    }

    if (empty($rows)) {
      $rows[] = array(array('data' => t('No statistics available.'), 'colspan' => 3));
    }

    drupal_set_title(check_plain($account->name));
    $output = theme('table', $header, $rows);
    $output .= theme('pager', NULL, 30, 0);
    return $output;
  }
  else {
    drupal_not_found();
  }
}
