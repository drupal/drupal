<?php

/**
 * @file
 * Admin page callbacks for the Statistics module.
 */

/**
 * Page callback: Displays the "recent hits" page.
 *
 * This displays the pages with recent hits in a given time interval that
 * haven't been flushed yet. The flush interval is set on the statistics
 * settings form, but is dependent on cron running.
 *
 * @return
 *   A render array containing information about the most recent hits.
 */
function statistics_recent_hits() {
  $header = array(
    array('data' => t('Timestamp'), 'field' => 'a.timestamp', 'sort' => 'desc'),
    array('data' => t('Page'), 'field' => 'a.path'),
    array('data' => t('User'), 'field' => 'u.name'),
    array('data' => t('Operations'))
  );

  $query = db_select('accesslog', 'a', array('target' => 'slave'))->extend('PagerDefault')->extend('TableSort');
  $query->join('users', 'u', 'a.uid = u.uid');
  $query
    ->fields('a', array('aid', 'timestamp', 'path', 'title', 'uid'))
    ->fields('u', array('name'))
    ->limit(30)
    ->orderByHeader($header);

  $result = $query->execute();
  $rows = array();
  foreach ($result as $log) {
    $rows[] = array(
      array('data' => format_date($log->timestamp, 'short'), 'class' => array('nowrap')),
      _statistics_format_item($log->title, $log->path),
      theme('username', array('account' => $log)),
      l(t('details'), "admin/reports/access/$log->aid"));
  }

  $build['statistics_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No statistics available.'),
  );
  $build['statistics_pager'] = array('#theme' => 'pager');
  return $build;
}

/**
 * Page callback: Displays statistics for the "top pages" (most accesses).
 *
 * This displays the pages with the most hits (the "top pages") within a given
 * time period that haven't been flushed yet. The flush interval is set on the
 * statistics settings form, but is dependent on cron running.
 *
 * @return
 *   A render array containing information about the top pages.
 */
function statistics_top_pages() {
  $header = array(
    array('data' => t('Hits'), 'field' => 'hits', 'sort' => 'desc'),
    array('data' => t('Page'), 'field' => 'path'),
    array('data' => t('Average page generation time'), 'field' => 'average_time'),
    array('data' => t('Total page generation time'), 'field' => 'total_time')
  );

  $query = db_select('accesslog', 'a', array('target' => 'slave'))->extend('PagerDefault')->extend('TableSort');
  $query->addExpression('COUNT(path)', 'hits');
  // MAX(title) avoids having empty node titles which otherwise causes
  // duplicates in the top pages list.
  $query->addExpression('MAX(title)', 'title');
  $query->addExpression('AVG(timer)', 'average_time');
  $query->addExpression('SUM(timer)', 'total_time');

  $query
    ->fields('a', array('path'))
    ->groupBy('path')
    ->limit(30)
    ->orderByHeader($header);

  $count_query = db_select('accesslog', 'a', array('target' => 'slave'));
  $count_query->addExpression('COUNT(DISTINCT path)');
  $query->setCountQuery($count_query);

  $result = $query->execute();
  $rows = array();
  foreach ($result as $page) {
    $rows[] = array($page->hits, _statistics_format_item($page->title, $page->path), t('%time ms', array('%time' => round($page->average_time))), format_interval(round($page->total_time / 1000)));
  }

  drupal_set_title(t('Top pages in the past %interval', array('%interval' => format_interval(variable_get('statistics_flush_accesslog_timer', 259200)))), PASS_THROUGH);
  $build['statistics_top_pages_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No statistics available.'),
  );
  $build['statistics_top_pages_pager'] = array('#theme' => 'pager');
  return $build;
}

/**
 * Page callback: Displays the "top visitors" page.
 *
 * This displays the pages with the top number of visitors in a given time
 * interval that haven't been flushed yet. The flush interval is set on the
 * statistics settings form, but is dependent on cron running.
 *
 * @return
 *   A render array containing the top visitors information.
 */
function statistics_top_visitors() {

  $header = array(
    array('data' => t('Hits'), 'field' => 'hits', 'sort' => 'desc'),
    array('data' => t('Visitor'), 'field' => 'u.name'),
    array('data' => t('Total page generation time'), 'field' => 'total'),
    array('data' => user_access('block IP addresses') ? t('Operations') : '', 'colspan' => 2),
  );
  $query = db_select('accesslog', 'a', array('target' => 'slave'))->extend('PagerDefault')->extend('TableSort');
  $query->leftJoin('blocked_ips', 'bl', 'a.hostname = bl.ip');
  $query->leftJoin('users', 'u', 'a.uid = u.uid');

  $query->addExpression('COUNT(a.uid)', 'hits');
  $query->addExpression('SUM(a.timer)', 'total');
  $query
    ->fields('a', array('uid', 'hostname'))
    ->fields('u', array('name'))
    ->fields('bl', array('iid'))
    ->groupBy('a.hostname')
    ->groupBy('a.uid')
    ->groupBy('u.name')
    ->groupBy('bl.iid')
    ->limit(30)
    ->orderByHeader($header)
    ->orderBy('a.hostname');

  $uniques_query = db_select('accesslog')->distinct();
  $uniques_query->fields('accesslog', array('uid', 'hostname'));
  $count_query = db_select($uniques_query);
  $count_query->addExpression('COUNT(*)');
  $query->setCountQuery($count_query);

  $result = $query->execute();
  $rows = array();
  $destination = drupal_get_destination();
  foreach ($result as $account) {
    $ban_link = $account->iid ? l(t('unblock IP address'), "admin/config/people/ip-blocking/delete/$account->iid", array('query' => $destination)) : l(t('block IP address'), "admin/config/people/ip-blocking/$account->hostname", array('query' => $destination));
    $rows[] = array($account->hits, ($account->uid ? theme('username', array('account' => $account)) : $account->hostname), format_interval(round($account->total / 1000)), (user_access('block IP addresses') && !$account->uid) ? $ban_link : '');
  }

  drupal_set_title(t('Top visitors in the past %interval', array('%interval' => format_interval(variable_get('statistics_flush_accesslog_timer', 259200)))), PASS_THROUGH);
  $build['statistics_top_visitors_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No statistics available.'),
  );
  $build['statistics_top_visitors_pager'] = array('#theme' => 'pager');
  return $build;
}

/**
 * Page callback: Displays the "top referrers" in the access logs.
 *
 * This displays the pages with the top referrers in a given time interval that
 * haven't been flushed yet. The flush interval is set on the statistics
 * settings form, but is dependent on cron running.
 *
 * @return
 *   A render array containing the top referrers information.
 */
function statistics_top_referrers() {
  drupal_set_title(t('Top referrers in the past %interval', array('%interval' => format_interval(variable_get('statistics_flush_accesslog_timer', 259200)))), PASS_THROUGH);

  $header = array(
    array('data' => t('Hits'), 'field' => 'hits', 'sort' => 'desc'),
    array('data' => t('Url'), 'field' => 'url'),
    array('data' => t('Last visit'), 'field' => 'last'),
  );
  $query = db_select('accesslog', 'a')->extend('PagerDefault')->extend('TableSort');

  $query->addExpression('COUNT(url)', 'hits');
  $query->addExpression('MAX(timestamp)', 'last');
  $query
    ->fields('a', array('url'))
    ->condition('url', '%' . $_SERVER['HTTP_HOST'] . '%', 'NOT LIKE')
    ->condition('url', '', '<>')
    ->groupBy('url')
    ->limit(30)
    ->orderByHeader($header);

  $count_query = db_select('accesslog', 'a', array('target' => 'slave'));
  $count_query->addExpression('COUNT(DISTINCT url)');
  $count_query
    ->condition('url', '%' . $_SERVER['HTTP_HOST'] . '%', 'NOT LIKE')
    ->condition('url', '', '<>');
  $query->setCountQuery($count_query);

  $result = $query->execute();
  $rows = array();
  foreach ($result as $referrer) {
    $rows[] = array($referrer->hits, _statistics_link($referrer->url), t('@time ago', array('@time' => format_interval(REQUEST_TIME - $referrer->last))));
  }

  $build['statistics_top_referrers_table'] = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No statistics available.'),
  );
  $build['statistics_top_referrers_pager'] = array('#theme' => 'pager');
  return $build;
}

/**
 * Page callback: Gathers page access statistics suitable for rendering.
 *
 * @param $aid
 *   The unique accesslog ID.
 *
 * @return
 *   A render array containing page access statistics. If information for the
 *   page was not found, drupal_not_found() is called.
 */
function statistics_access_log($aid) {
  $access = db_query('SELECT a.*, u.name FROM {accesslog} a LEFT JOIN {users} u ON a.uid = u.uid WHERE aid = :aid', array(':aid' => $aid))->fetch();
  if ($access) {
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
      format_date($access->timestamp, 'long')
    );
    $rows[] = array(
      array('data' => t('User'), 'header' => TRUE),
      theme('username', array('account' => $access))
    );
    $rows[] = array(
      array('data' => t('Hostname'), 'header' => TRUE),
      check_plain($access->hostname)
    );

    $build['statistics_table'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
    );
    return $build;
  }
  return MENU_NOT_FOUND;
}

/**
 * Form constructor for the statistics administration form.
 *
 * @ingroup forms
 * @see system_settings_form()
 */
function statistics_settings_form() {
  // Access log settings.
  $form['access'] = array(
    '#type' => 'fieldset',
    '#title' => t('Access log settings'),
  );
  $form['access']['statistics_enable_access_log'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable access log'),
    '#default_value' => variable_get('statistics_enable_access_log', 0),
    '#description' => t('Log each page access. Required for referrer statistics.'),
  );
  $form['access']['statistics_flush_accesslog_timer'] = array(
    '#type' => 'select',
    '#title' => t('Discard access logs older than'),
    '#default_value' => variable_get('statistics_flush_accesslog_timer', 259200),
    '#options' => array(0 => t('Never')) + drupal_map_assoc(array(3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800), 'format_interval'),
    '#description' => t('Older access log entries (including referrer statistics) will be automatically discarded. (Requires a correctly configured <a href="@cron">cron maintenance task</a>.)', array('@cron' => url('admin/reports/status'))),
  );

  // Content counter settings.
  $form['content'] = array(
    '#type' => 'fieldset',
    '#title' => t('Content viewing counter settings'),
  );
  $form['content']['statistics_count_content_views'] = array(
    '#type' => 'checkbox',
    '#title' => t('Count content views'),
    '#default_value' => variable_get('statistics_count_content_views', 0),
    '#description' => t('Increment a counter each time content is viewed.'),
  );
  $form['content']['statistics_count_content_views_ajax'] = array(
    '#type' => 'checkbox',
    '#title' => t('Use Ajax to increment the counter'),
    '#default_value' => variable_get('statistics_count_content_views_ajax', 0),
    '#description' => t('Perform the count asynchronously after page load rather than during page generation.'),
    '#states' => array(
      'disabled' => array(
        ':input[name="statistics_count_content_views"]' => array('checked' => FALSE),
      ),
    ),
  );

  return system_settings_form($form);
}
