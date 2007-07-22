<?php
// $Id: blog.pages.inc,v 1.1 2007/07/22 06:48:25 dries Exp $

/**
 * @file
 * Page callback file for the blog module.
 */

/**
 * Menu callback; displays a Drupal page containing recent blog entries of a given user.
 */
function blog_page_user($account) {
  global $user;

  $account = user_load(array((is_numeric($uid) ? 'uid' : 'name') => $uid, 'status' => 1));

  if (!empty($account->uid)) {
    drupal_set_title($title = t("@name's blog", array('@name' => $account->name)));

    if (($account->uid == $user->uid) && user_access('edit own blog')) {
      $output = '<li>'. l(t('Post new blog entry.'), "node/add/blog") .'</li>';
    }
    else if ($account->uid == $user->uid) {
      $output = '<li>'. t('You are not allowed to post a new blog entry.') .'</li>';
    }

    if ($output) {
      $output = '<ul>'. $output .'</ul>';
    }
    else {
      $output = '';
    }

    $result = pager_query(db_rewrite_sql("SELECT n.nid, n.sticky, n.created FROM {node} n WHERE n.type = 'blog' AND n.uid = %d AND n.status = 1 ORDER BY n.sticky DESC, n.created DESC"), variable_get('default_nodes_main', 10), 0, NULL, $account->uid);
    while ($node = db_fetch_object($result)) {
      $output .= node_view(node_load($node->nid), 1);
    }
    $output .= theme('pager', NULL, variable_get('default_nodes_main', 10));
    drupal_add_feed(url('blog/'. $account->uid .'/feed'), t('RSS - !title', array('!title' => $title)));

    return $output;
  }
  else {
    drupal_not_found();
  }
}

/**
 * Menu callback; displays a Drupal page containing recent blog entries of all users.
 */
function blog_page_last() {
  global $user;

  $output = '';

  $result = pager_query(db_rewrite_sql("SELECT n.nid, n.created FROM {node} n WHERE n.type = 'blog' AND n.status = 1 ORDER BY n.sticky DESC, n.created DESC"), variable_get('default_nodes_main', 10));

  while ($node = db_fetch_object($result)) {
    $output .= node_view(node_load($node->nid), 1);
  }
  $output .= theme('pager', NULL, variable_get('default_nodes_main', 10));
  drupal_add_feed(url('blog/feed'), t('RSS - blogs'));

  return $output;
}

/**
 * Menu callback; displays an RSS feed containing recent blog entries of a given user.
 */
function blog_feed_user($uid = 0) {
  global $user;

  if ($uid) {
    $account = user_load(array('uid' => $uid, 'status' => 1));
  }
  else {
    $account = $user;
  }

  $result = db_query_range(db_rewrite_sql("SELECT n.nid, n.created FROM {node} n  WHERE n.type = 'blog' AND n.uid = %d AND n.status = 1 ORDER BY n.created DESC"), $uid, 0, variable_get('feed_default_items', 10));
  $channel['title'] = $account->name ."'s blog";
  $channel['link'] = url("blog/$uid", array('absolute' => TRUE));

  while ($row = db_fetch_object($result)) {
    $items[] = $row->nid;
  }
  node_feed($items, $channel);
}

/**
 * Menu callback; displays an RSS feed containing recent blog entries of all users.
 */
function blog_feed_last() {
  $result = db_query_range(db_rewrite_sql("SELECT n.nid, n.created FROM {node} n WHERE n.type = 'blog' AND n.status = 1 ORDER BY n.created DESC"), 0, variable_get('feed_default_items', 10));
  $channel['title'] = variable_get('site_name', 'Drupal') .' blogs';
  $channel['link'] = url('blog', array('absolute' => TRUE));

  while ($row = db_fetch_object($result)) {
    $items[] = $row->nid;
  }

  node_feed($items, $channel);
}
