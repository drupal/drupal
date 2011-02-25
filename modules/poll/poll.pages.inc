<?php

/**
 * @file
 * User page callbacks for the poll module.
 */

/**
 * Menu callback to provide a simple list of all polls available.
 */
function poll_page() {
  // List all polls.
  $sql = db_rewrite_sql("SELECT n.nid, n.title, p.active, n.created, SUM(c.chvotes) AS votes FROM {node} n INNER JOIN {poll} p ON n.nid = p.nid INNER JOIN {poll_choices} c ON n.nid = c.nid WHERE n.status = 1 GROUP BY n.nid, n.title, p.active, n.created ORDER BY n.created DESC");
  // Count all polls for the pager.
  $count_sql = db_rewrite_sql('SELECT COUNT(*) FROM {node} n INNER JOIN {poll} p ON n.nid = p.nid WHERE n.status = 1');
  $result = pager_query($sql, 15, 0, $count_sql);
  $output = '<ul>';
  while ($node = db_fetch_object($result)) {
    $output .= '<li>'. l($node->title, "node/$node->nid") .' - '. format_plural($node->votes, '1 vote', '@count votes') .' - '. ($node->active ? t('open') : t('closed')) .'</li>';
  }
  $output .= '</ul>';
  $output .= theme("pager", NULL, 15);
  return $output;
}

/**
 * Callback for the 'votes' tab for polls you can see other votes on
 */
function poll_votes($node) {
  drupal_set_title(check_plain($node->title));
  $output = t('This table lists all the recorded votes for this poll. If anonymous users are allowed to vote, they will be identified by the IP address of the computer they used when they voted.');

  $header[] = array('data' => t('Visitor'), 'field' => 'u.name');
  $header[] = array('data' => t('Vote'), 'field' => 'pv.chorder');

  $result = pager_query("SELECT pv.chorder, pv.uid, pv.hostname, u.name FROM {poll_votes} pv LEFT JOIN {users} u ON pv.uid = u.uid WHERE pv.nid = %d". tablesort_sql($header), 20, 0, NULL, $node->nid);
  $rows = array();
  while ($vote = db_fetch_object($result)) {
    $rows[] = array(
      $vote->name ? theme('username', $vote) : check_plain($vote->hostname),
      check_plain($node->choice[$vote->chorder]['chtext']));
  }
  $output .= theme('table', $header, $rows);
  $output .= theme('pager', NULL, 20, 0);
  return $output;
}

/**
 * Callback for the 'results' tab for polls you can vote on
 */
function poll_results($node) {
  drupal_set_title(check_plain($node->title));
  $node->show_results = TRUE;
  return node_show($node, 0);
}
