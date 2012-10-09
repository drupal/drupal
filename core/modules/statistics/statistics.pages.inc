<?php

/**
 * @file
 * User page callbacks for the Statistics module.
 */

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Page callback: Displays statistics for a node.
 *
 * @return array
 *   A render array containing node statistics. If information for the node was
 *   not found, this will throw a NotFoundHttpException.
 *
 * @see statistics_menu()
 */
function statistics_node_tracker() {
  if ($node = node_load(arg(1))) {
    $header = array(
      array('data' => t('Time'), 'field' => 'a.timestamp', 'sort' => 'desc'),
      array('data' => t('Referrer'), 'field' => 'a.url'),
      array('data' => t('User'), 'field' => 'u.name'),
      t('Operations'),
    );

    $query = db_select('accesslog', 'a', array('target' => 'slave'))
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->join('users', 'u', 'a.uid = u.uid');

    $query
      ->fields('a', array('aid', 'timestamp', 'url', 'uid'))
      ->fields('u', array('name'))
      ->condition(db_or()
        ->condition('a.path', 'node/' . $node->nid)
        ->condition('a.path', 'node/' . $node->nid . '/%', 'LIKE'))
      ->limit(30)
      ->orderByHeader($header);

    $result = $query->execute();
    $rows = array();
    foreach ($result as $log) {
      $row = array();
      $row[] = array('data' => format_date($log->timestamp, 'short'), 'class' => array('nowrap'));
      $row[] = _statistics_link($log->url);
      $row[] = theme('username', array('account' => $log));
      $links = array();
      $links['details'] = array(
        'title' => t('details'),
        'href' => "admin/reports/access/$log->aid",
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }

    // Do not use $node->label() here, because $node comes from the database.
    drupal_set_title($node->title);
    $build['statistics_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No statistics available.'),
    );
    $build['statistics_pager'] = array('#theme' => 'pager');
    return $build;
  }
  else {
    throw new NotFoundHttpException();
  }
}

/**
 * Page callback: Displays statistics for a user.
 *
 * @return array
 *   A render array containing user statistics. If information for the user was
 *   not found, this will throw a NotFoundHttpException.
 *
 * @see statistics_menu()
 */
function statistics_user_tracker() {
  if ($account = user_load(arg(1))) {

    $header = array(
      array('data' => t('Timestamp'), 'field' => 'timestamp', 'sort' => 'desc'),
      array('data' => t('Page'), 'field' => 'path'),
      t('Operations'),
    );
    $query = db_select('accesslog', 'a', array('target' => 'slave'))
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query
      ->fields('a', array('aid', 'timestamp', 'path', 'title'))
      ->condition('uid', $account->uid)
      ->limit(30)
      ->orderByHeader($header);

    $result = $query->execute();
    $rows = array();
    foreach ($result as $log) {
      $row = array();
      $row[] = array('data' => format_date($log->timestamp, 'short'), 'class' => array('nowrap'));
      $row[] = _statistics_format_item($log->title, $log->path);
      $links = array();
      $links['details'] = array(
        'title' => t('details'),
        'href' => "admin/reports/access/$log->aid",
      );
      $row[] = array(
        'data' => array(
          '#type' => 'operations',
          '#links' => $links,
        ),
      );
      $rows[] = $row;
    }

    drupal_set_title(user_format_name($account));
    $build['statistics_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No statistics available.'),
    );
    $build['statistics_pager'] = array('#theme' => 'pager');
    return $build;
  }
  else {
    throw new NotFoundHttpException();
  }
}
