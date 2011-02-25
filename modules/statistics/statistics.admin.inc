<?php

/**
 * @file
 * Admin page callbacks for the statistics module.
 */

/**
 * Menu callback; presents the "recent hits" page.
 */
function statistics_recent_hits() {
  $header = array(
    array('data' => t('Timestamp'), 'field' => 'a.timestamp', 'sort' => 'desc'),
    array('data' => t('Page'), 'field' => 'a.path'),
    array('data' => t('User'), 'field' => 'u.name'),
    array('data' => t('Operations'))
  );

  $sql = 'SELECT a.aid, a.path, a.title, a.uid, u.name, a.timestamp FROM {accesslog} a LEFT JOIN {users} u ON u.uid = a.uid'. tablesort_sql($header);

  $result = pager_query($sql, 30);
  $rows = array();
  while ($log = db_fetch_object($result)) {
    $rows[] = array(
      array('data' => format_date($log->timestamp, 'small'), 'class' => 'nowrap'),
      _statistics_format_item($log->title, $log->path),
      theme('username', $log),
      l(t('details'), "admin/reports/access/$log->aid"));
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No statistics available.'), 'colspan' => 4));
  }

  $output = theme('table', $header, $rows);
  $output .= theme('pager', NULL, 30, 0);
  return $output;
}

/**
 * Menu callback; presents the "top pages" page.
 */
function statistics_top_pages() {
  // MAX(title) avoids having empty node titles which otherwise causes duplicates in the top pages list
  $sql = "SELECT COUNT(path) AS hits, path, MAX(title) AS title, AVG(timer) AS average_time, SUM(timer) AS total_time FROM {accesslog} GROUP BY path";
  $sql_cnt = "SELECT COUNT(DISTINCT(path)) FROM {accesslog}";

  $header = array(
    array('data' => t('Hits'), 'field' => 'hits', 'sort' => 'desc'),
    array('data' => t('Page'), 'field' => 'path'),
    array('data' => t('Average page generation time'), 'field' => 'average_time'),
    array('data' => t('Total page generation time'), 'field' => 'total_time')
  );
  $sql .= tablesort_sql($header);
  $result = pager_query($sql, 30, 0, $sql_cnt);

  $rows = array();
  while ($page = db_fetch_object($result)) {
    $rows[] = array($page->hits, _statistics_format_item($page->title, $page->path), t('%time ms', array('%time' => round($page->average_time))), format_interval(round($page->total_time / 1000)));
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No statistics available.'), 'colspan' => 4));
  }

  drupal_set_title(t('Top pages in the past %interval', array('%interval' => format_interval(variable_get('statistics_flush_accesslog_timer', 259200)))));
  $output = theme('table', $header, $rows);
  $output .= theme('pager', NULL, 30, 0);
  return $output;
}

/**
 * Menu callback; presents the "top visitors" page.
 */
function statistics_top_visitors() {

  $header = array(
    array('data' => t('Hits'), 'field' => 'hits', 'sort' => 'desc'),
    array('data' => t('Visitor'), 'field' => 'u.name'),
    array('data' => t('Total page generation time'), 'field' => 'total'),
    array('data' => t('Operations'))
  );

  $sql = "SELECT COUNT(a.uid) AS hits, a.uid, u.name, a.hostname, SUM(a.timer) AS total, ac.aid FROM {accesslog} a LEFT JOIN {access} ac ON ac.type = 'host' AND LOWER(a.hostname) LIKE (ac.mask) LEFT JOIN {users} u ON a.uid = u.uid GROUP BY a.hostname, a.uid, u.name, ac.aid". tablesort_sql($header);
  $sql_cnt = "SELECT COUNT(*) FROM (SELECT DISTINCT uid, hostname FROM {accesslog}) AS unique_visits";
  $result = pager_query($sql, 30, 0, $sql_cnt);

  $rows = array();
  while ($account = db_fetch_object($result)) {
    $qs = drupal_get_destination();
    $ban_link = $account->aid ? l(t('unban'), "admin/user/rules/delete/$account->aid", array('query' => $qs)) : l(t('ban'), "admin/user/rules/add/$account->hostname/host", array('query' => $qs));
    $rows[] = array($account->hits, ($account->uid ? theme('username', $account) : $account->hostname), format_interval(round($account->total / 1000)), $ban_link);
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No statistics available.'), 'colspan' => 4));
  }

  drupal_set_title(t('Top visitors in the past %interval', array('%interval' => format_interval(variable_get('statistics_flush_accesslog_timer', 259200)))));
  $output = theme('table', $header, $rows);
  $output .= theme('pager', NULL, 30, 0);
  return $output;
}

/**
 * Menu callback; presents the "referrer" page.
 */
function statistics_top_referrers() {
  $query = "SELECT url, COUNT(url) AS hits, MAX(timestamp) AS last FROM {accesslog} WHERE url NOT LIKE '%%%s%%' AND url <> '' GROUP BY url";
  $query_cnt = "SELECT COUNT(DISTINCT(url)) FROM {accesslog} WHERE url <> '' AND url NOT LIKE '%%%s%%'";
  drupal_set_title(t('Top referrers in the past %interval', array('%interval' => format_interval(variable_get('statistics_flush_accesslog_timer', 259200)))));

  $header = array(
    array('data' => t('Hits'), 'field' => 'hits', 'sort' => 'desc'),
    array('data' => t('Url'), 'field' => 'url'),
    array('data' => t('Last visit'), 'field' => 'last'),
  );

  $query .= tablesort_sql($header);
  $result = pager_query($query, 30, 0, $query_cnt, $_SERVER['HTTP_HOST']);

  $rows = array();
  while ($referrer = db_fetch_object($result)) {
    $rows[] = array($referrer->hits, _statistics_link($referrer->url), t('@time ago', array('@time' => format_interval(time() - $referrer->last))));
  }

  if (empty($rows)) {
    $rows[] = array(array('data' => t('No statistics available.'), 'colspan' => 3));
  }

  $output = theme('table', $header, $rows);
  $output .= theme('pager', NULL, 30, 0);
  return $output;
}

/**
 * Menu callback; Displays recent page accesses.
 */
function statistics_access_log($aid) {
  $result = db_query('SELECT a.*, u.name FROM {accesslog} a LEFT JOIN {users} u ON a.uid = u.uid WHERE aid = %d', $aid);
  if ($access = db_fetch_object($result)) {
    $rows[] = array(
      array('data' => t('URL'), 'header' => TRUE),
      l(url($access->path, array('absolute' => TRUE)), $access->path)
    );
    // It is safe to avoid filtering $access->title through check_plain because
    // it comes from drupal_get_title().
    $rows[] = array(
      array('data' => t('Title'), 'header' => TRUE),
      $access->title
    );
    $rows[] = array(
      array('data' => t('Referrer'), 'header' => TRUE),
      ($access->url ? l($access->url, $access->url) : '')
    );
    $rows[] = array(
      array('data' => t('Date'), 'header' => TRUE),
      format_date($access->timestamp, 'large')
    );
    $rows[] = array(
      array('data' => t('User'), 'header' => TRUE),
      theme('username', $access)
    );
    $rows[] = array(
      array('data' => t('Hostname'), 'header' => TRUE),
      check_plain($access->hostname)
    );

    return theme('table', array(), $rows);
  }
  else {
    drupal_not_found();
  }
}

/**
 * Form builder; Configure access logging.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function statistics_access_logging_settings() {
  // Access log settings:
  $options = array('1' => t('Enabled'), '0' => t('Disabled'));
  $form['access'] = array(
    '#type' => 'fieldset',
    '#title' => t('Access log settings'));
  $form['access']['statistics_enable_access_log'] = array(
    '#type' => 'radios',
    '#title' => t('Enable access log'),
    '#default_value' => variable_get('statistics_enable_access_log', 0),
    '#options' => $options,
    '#description' => t('Log each page access. Required for referrer statistics.'));
  $period = array('0' => t('Never')) + drupal_map_assoc(array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800), 'format_interval');
  $form['access']['statistics_flush_accesslog_timer'] = array(
    '#type' => 'select',
    '#title' => t('Discard access logs older than'),
    '#default_value'   => variable_get('statistics_flush_accesslog_timer', 259200),
    '#options' => $period,
    '#description' => t('Older access log entries (including referrer statistics) will be automatically discarded. (Requires a correctly configured <a href="@cron">cron maintenance task</a>.)', array('@cron' => url('admin/reports/status'))));

  // count content views settings
  $form['content'] = array(
    '#type' => 'fieldset',
    '#title' => t('Content viewing counter settings'));
  $form['content']['statistics_count_content_views'] = array(
    '#type' => 'radios',
    '#title' => t('Count content views'),
    '#default_value' => variable_get('statistics_count_content_views', 0),
    '#options' => $options,
    '#description' => t('Increment a counter each time content is viewed.'));

  return system_settings_form($form);
}
