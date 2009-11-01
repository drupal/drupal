<?php
// $Id$

/**
 * @file
 * User page callbacks for the tracker module.
 */


/**
 * Menu callback; prints a listing of active nodes on the site.
 */
function tracker_page($account = NULL, $set_title = FALSE) {
  if ($account) {
    $query = db_select('tracker_user', 't')->extend('PagerDefault');
    $query->condition('t.uid', $account->uid);

    if ($set_title) {
      // When viewed from user/%user/track, display the name of the user
      // as page title -- the tab title remains Track so this needs to be done
      // here and not in the menu definition.
      drupal_set_title(format_username($account));
    }
  }
  else {
    $query = db_select('tracker_node', 't', array('target' => 'slave'))->extend('PagerDefault');
  }

  // This array acts as a placeholder for the data selected later
  // while keeping the correct order.
  $nodes = $query
    ->addTag('node_access')
    ->fields('t', array('nid', 'changed'))
    ->condition('t.published', 1)
    ->orderBy('t.changed', 'DESC')
    ->limit(25)
    ->execute()
    ->fetchAllAssoc('nid');

  if (!empty($nodes)) {
    // Now, get the data and put into the placeholder array
    $result = db_query('SELECT n.nid, n.title, n.type, n.changed, n.uid, u.name, l.comment_count FROM {node} n INNER JOIN {node_comment_statistics} l ON n.nid = l.nid INNER JOIN {users} u ON n.uid = u.uid WHERE n.nid IN (:nids)', array(':nids' => array_keys($nodes)), array('target' => 'slave'));
    foreach ($result as $node) {
      $node->last_activity = $nodes[$node->nid]->changed;
      $nodes[$node->nid] = $node;
    }

    // Finally display the data
    $rows = array();
    foreach ($nodes as $node) {
      // Determine the number of comments:
      $comments = 0;
      if ($node->comment_count) {
        $comments = $node->comment_count;

        if ($new = comment_num_new($node->nid)) {
          $comments .= '<br />';
          $comments .= l(format_plural($new, '1 new', '@count new'), 'node/'. $node->nid, array('fragment' => 'new'));
        }
      }

      $rows[] = array(
        check_plain(node_type_get_name($node->type)),
        l($node->title, 'node/' . $node->nid) . ' ' . theme('mark', array('type' => node_mark($node->nid, $node->changed))),
        theme('username', array('account' => $node)),
        array('class' => array('replies'), 'data' => $comments),
        t('!time ago', array('!time' => format_interval(REQUEST_TIME - $node->last_activity)))
      );
    }
  }
  else {
    $rows[] = array(array('data' => t('No posts available.'), 'colspan' => '5'));
  }

  $page['tracker'] = array(
    '#rows' => $rows,
    '#header' => array(t('Type'), t('Post'), t('Author'), t('Replies'), t('Last updated')),
    '#theme' => 'table',
    '#attached' => array(
      'css' => array(drupal_get_path('module', 'tracker') . '/tracker.css' => array('preprocess' => FALSE)),
    ),
  );
  $page['pager'] = array(
    '#theme' => 'pager',
    '#quantity' => 25,
    '#weight' => 10,
  );
  $page['#sorted'] = TRUE;

  return $page;
}
