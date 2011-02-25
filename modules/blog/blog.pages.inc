<?php

/**
 * @file
 * Page callback file for the blog module.
 */

/**
 * Menu callback; displays a Drupal page containing recent blog entries of a given user.
 */
function blog_page_user($account) {
  global $user;

  drupal_set_title($title = t("@name's blog", array('@name' => $account->name)));

  $items = array();

  if (($account->uid == $user->uid) && user_access('create blog entries')) {
    $items[] = l(t('Post new blog entry.'), "node/add/blog");
  }
  else if ($account->uid == $user->uid) {
    $items[] = t('You are not allowed to post a new blog entry.');
  }

  $output = theme('item_list', $items);

  $result = pager_query(db_rewrite_sql("SELECT n.nid, n.sticky, n.created FROM {node} n WHERE n.type = 'blog' AND n.uid = %d AND n.status = 1 ORDER BY n.sticky DESC, n.created DESC"), variable_get('default_nodes_main', 10), 0, NULL, $account->uid);
  $has_posts = FALSE;
  
  while ($node = db_fetch_object($result)) {
    $output .= node_view(node_load($node->nid), 1);
    $has_posts = TRUE;
  }
  
  if ($has_posts) {
    $output .= theme('pager', NULL, variable_get('default_nodes_main', 10));
  }
  else {
    if ($account->uid == $user->uid) {
      drupal_set_message(t('You have not created any blog entries.'));
    }
    else {
      drupal_set_message(t('!author has not created any blog entries.', array('!author' => theme('username', $account))));
    }
  }
  drupal_add_feed(url('blog/'. $account->uid .'/feed'), t('RSS - !title', array('!title' => $title)));

  return $output;
}

/**
 * Menu callback; displays a Drupal page containing recent blog entries of all users.
 */
function blog_page_last() {
  global $user;

  $output = '';
  $items = array();

  if (user_access('create blog entries')) {
    $items[] = l(t('Create new blog entry.'), "node/add/blog");
  }

  $output = theme('item_list', $items);

  $result = pager_query(db_rewrite_sql("SELECT n.nid, n.created FROM {node} n WHERE n.type = 'blog' AND n.status = 1 ORDER BY n.sticky DESC, n.created DESC"), variable_get('default_nodes_main', 10));
  $has_posts = FALSE;

  while ($node = db_fetch_object($result)) {
    $output .= node_view(node_load($node->nid), 1);
    $has_posts = TRUE;
  }
  
  if ($has_posts) {
    $output .= theme('pager', NULL, variable_get('default_nodes_main', 10));
  }
  else {
    drupal_set_message(t('No blog entries have been created.'));
  }
  drupal_add_feed(url('blog/feed'), t('RSS - blogs'));

  return $output;
}

/**
 * Menu callback; displays an RSS feed containing recent blog entries of a given user.
 */
function blog_feed_user($account) {
  $result = db_query_range(db_rewrite_sql("SELECT n.nid, n.created FROM {node} n  WHERE n.type = 'blog' AND n.uid = %d AND n.status = 1 ORDER BY n.created DESC"), $account->uid, 0, variable_get('feed_default_items', 10));
  $channel['title'] = t("!name's blog", array('!name' => $account->name));
  $channel['link'] = url('blog/'. $account->uid, array('absolute' => TRUE));

  $items = array();
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
  $channel['title'] = t('!site_name blogs', array('!site_name' => variable_get('site_name', 'Drupal')));
  $channel['link'] = url('blog', array('absolute' => TRUE));

  $items = array();
  while ($row = db_fetch_object($result)) {
    $items[] = $row->nid;
  }

  node_feed($items, $channel);
}
