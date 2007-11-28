<?php
// $Id$

/**
 * @file
 * User page callbacks for the tracker module.
 */


/**
 * Menu callback. Prints a listing of active nodes on the site.
 */
function tracker_page($account = NULL, $set_title = FALSE) {
  // Add CSS
  drupal_add_css(drupal_get_path('module', 'tracker') .'/tracker.css', 'module', 'all', FALSE);

  if ($account) {
    if ($set_title) {
      // When viewed from user/%user/track, display the name of the user
      // as page title -- the tab title remains Track so this needs to be done
      // here and not in the menu definiton.
      drupal_set_title(check_plain($account->name));
    }
  // TODO: These queries are very expensive, see http://drupal.org/node/105639
    $sql = 'SELECT DISTINCT(n.nid), n.title, n.type, n.changed, n.uid, u.name, GREATEST(n.changed, l.last_comment_timestamp) AS last_updated, l.comment_count FROM {node} n INNER JOIN {node_comment_statistics} l ON n.nid = l.nid INNER JOIN {users} u ON n.uid = u.uid LEFT JOIN {comments} c ON n.nid = c.nid AND (c.status = %d OR c.status IS NULL) WHERE n.status = 1 AND (n.uid = %d OR c.uid = %d) ORDER BY last_updated DESC';
    $sql = db_rewrite_sql($sql);
    $sql_count = 'SELECT COUNT(DISTINCT(n.nid)) FROM {node} n LEFT JOIN {comments} c ON n.nid = c.nid AND (c.status = %d OR c.status IS NULL) WHERE n.status = 1 AND (n.uid = %d OR c.uid = %d)';
    $sql_count = db_rewrite_sql($sql_count);
    $result = pager_query($sql, 25, 0, $sql_count, COMMENT_PUBLISHED, $account->uid, $account->uid);
  }
  else {
    $sql = 'SELECT DISTINCT(n.nid), n.title, n.type, n.changed, n.uid, u.name, GREATEST(n.changed, l.last_comment_timestamp) AS last_updated, l.comment_count FROM {node} n INNER JOIN {users} u ON n.uid = u.uid INNER JOIN {node_comment_statistics} l ON n.nid = l.nid WHERE n.status = 1 ORDER BY last_updated DESC';
    $sql = db_rewrite_sql($sql);
    $sql_count = 'SELECT COUNT(n.nid) FROM {node} n WHERE n.status = 1';
    $sql_count = db_rewrite_sql($sql_count);
    $result = pager_query($sql, 25, 0, $sql_count);
  }

  $rows = array();
  while ($node = db_fetch_object($result)) {
    // Determine the number of comments:
    $comments = 0;
    if ($node->comment_count) {
      $comments = $node->comment_count;

      if ($new = comment_num_new($node->nid)) {
        $comments .= '<br />';
        $comments .= l(format_plural($new, '1 new', '@count new'), "node/$node->nid", array('query' => comment_new_page_count($node->comment_count, $new, $node), 'fragment' => 'new'));
      }
    }

    $rows[] = array(
      check_plain(node_get_types('name', $node->type)),
      l($node->title, "node/$node->nid") .' '. theme('mark', node_mark($node->nid, $node->changed)),
      theme('username', $node),
      array('class' => 'replies', 'data' => $comments),
      t('!time ago', array('!time' => format_interval(time() - $node->last_updated)))
    );
  }

  if (!$rows) {
    $rows[] = array(array('data' => t('No posts available.'), 'colspan' => '5'));
  }

  $header = array(t('Type'), t('Post'), t('Author'), t('Replies'), t('Last updated'));

  $output = '<div id="tracker">';
  $output .= theme('table', $header, $rows);
  $output .= theme('pager', NULL, 25, 0);
  $output .= '</div>';

  return $output;
}
