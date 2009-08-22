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
  // TODO: These queries are very expensive, see http://drupal.org/node/105639
  $query = db_select('node', 'n', array('target' => 'slave'))->extend('PagerDefault');
  $query->join('users', 'u', 'n.uid = u.uid');
  $query->join('node_comment_statistics', 'l', 'n.nid = l.nid');
  $query->addExpression('GREATEST(n.changed, l.last_comment_timestamp)', 'last_updated');
  $query
    ->distinct()
    ->fields('n', array('nid', 'title', 'type', 'changed', 'uid'))
    ->fields('u', array('name'))
    ->fields('l', array('comment_count'))
    ->condition('n.status', 1)
    ->orderBy('last_updated', 'DESC')
    ->addTag('node_access')
    ->limit(25);

  if ($account) {
    if ($set_title) {
      // When viewed from user/%user/track, display the name of the user
      // as page title -- the tab title remains Track so this needs to be done
      // here and not in the menu definition.
      drupal_set_title($account->name);
    }
    $query->leftJoin('comment', 'c', 'n.nid = c.nid AND (c.status = :status OR c.status IS NULL)', array(':status' => COMMENT_PUBLISHED));
    $query->condition(db_or()
      ->condition('n.uid', $account->uid)
      ->condition('c.uid', $account->uid)
    );
  }

  $result = $query->execute();

  $rows = array();
  foreach ($result as $node) {
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
      check_plain(node_type_get_name($node->type)),
      l($node->title, "node/$node->nid") . ' ' . theme('mark', node_mark($node->nid, $node->changed)),
      theme('username', $node),
      array('class' => array('replies'), 'data' => $comments),
      t('!time ago', array('!time' => format_interval(REQUEST_TIME - $node->last_updated)))
    );
  }

  if (!$rows) {
    $rows[] = array(array('data' => t('No posts available.'), 'colspan' => '5'));
  }

  $page['tracker'] = array(
    '#rows' => $rows,
    '#header' => array(t('Type'), t('Post'), t('Author'), t('Replies'), t('Last updated')),
    '#theme' => 'table',
    '#attached_css' => array(drupal_get_path('module', 'tracker') . '/tracker.css' => array('preprocess' => FALSE)),
  );
  $page['pager'] = array(
    '#theme' => 'pager',
    '#quantity' => 25,
    '#weight' => 10,
  );
  $page['#sorted'] = TRUE;

  return $page;
}
