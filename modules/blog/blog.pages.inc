<?php
// $Id: blog.pages.inc,v 1.26 2009/12/21 13:47:31 dries Exp $

/**
 * @file
 * Page callback file for the blog module.
 */

/**
 * Menu callback; displays a Drupal page containing recent blog entries of a given user.
 */
function blog_page_user($account) {
  global $user;

  drupal_set_title($title = t("@name's blog", array('@name' => format_username($account))), PASS_THROUGH);

  $items = array();

  if (($account->uid == $user->uid) && user_access('create blog content')) {
    $items[] = l(t('Post new blog entry.'), "node/add/blog");
  }
  elseif ($account->uid == $user->uid) {
    $items[] = t('You are not allowed to post a new blog entry.');
  }

  $build['blog_actions'] = array(
    '#items' => $items,
    '#theme' => 'item_list',
    '#weight' => -1,
  );

  $query = db_select('node', 'n')->extend('PagerDefault');
  $nids = $query
    ->fields('n', array('nid', 'sticky', 'created'))
    ->condition('type', 'blog')
    ->condition('uid', $account->uid)
    ->condition('status', 1)
    ->orderBy('sticky', 'DESC')
    ->orderBy('created', 'DESC')
    ->limit(variable_get('default_nodes_main', 10))
    ->addTag('node_access')
    ->execute()
    ->fetchCol();

  if (!empty($nids)) {
    $nodes = node_load_multiple($nids);
    $build += node_view_multiple($nodes);
    $build['pager'] = array(
      '#theme' => 'pager',
      '#weight' => 5,
    );
  }
  else {
    if ($account->uid == $user->uid) {
      drupal_set_message(t('You have not created any blog entries.'));
    }
    else {
      drupal_set_message(t('!author has not created any blog entries.', array('!author' => theme('username', array('account' => $account)))));
    }
  }
  drupal_add_feed(url('blog/' . $account->uid . '/feed'), t('RSS - !title', array('!title' => $title)));

  return $build;
}

/**
 * Menu callback; displays a Drupal page containing recent blog entries of all users.
 */
function blog_page_last() {
  global $user;
  $build = array();

  $query = db_select('node', 'n')->extend('PagerDefault');
  $nids = $query
    ->fields('n', array('nid', 'sticky', 'created'))
    ->condition('type', 'blog')
    ->condition('status', 1)
    ->orderBy('sticky', 'DESC')
    ->orderBy('created', 'DESC')
    ->limit(variable_get('default_nodes_main', 10))
    ->addTag('node_access')
    ->execute()
    ->fetchCol();

  if (!empty($nids)) {
    $nodes = node_load_multiple($nids);
    $build += node_view_multiple($nodes);
    $build['pager'] = array(
      '#theme' => 'pager',
      '#weight' => 5,
    );
  }
  else {
    drupal_set_message(t('No blog entries have been created.'));
  }
  drupal_add_feed(url('blog/feed'), t('RSS - blogs'));

  return $build;
}

/**
 * Menu callback; displays an RSS feed containing recent blog entries of a given user.
 */
function blog_feed_user($account) {

  $nids = db_select('node', 'n')
    ->fields('n', array('nid', 'created'))
    ->condition('type', 'blog')
    ->condition('uid', $account->uid)
    ->condition('status', 1)
    ->orderBy('created', 'DESC')
    ->range(0, variable_get('feed_default_items', 10))
    ->addTag('node_access')
    ->execute()
    ->fetchCol();

  $channel['title'] = t("!name's blog", array('!name' => format_username($account)));
  $channel['link'] = url('blog/' . $account->uid, array('absolute' => TRUE));

  node_feed($nids, $channel);
}

/**
 * Menu callback; displays an RSS feed containing recent blog entries of all users.
 */
function blog_feed_last() {
  $nids = db_select('node', 'n')
    ->fields('n', array('nid', 'created'))
    ->condition('type', 'blog')
    ->condition('status', 1)
    ->orderBy('created', 'DESC')
    ->range(0, variable_get('feed_default_items', 10))
    ->addTag('node_access')
    ->execute()
    ->fetchCol();

  $channel['title'] = t('!site_name blogs', array('!site_name' => variable_get('site_name', 'Drupal')));
  $channel['link'] = url('blog', array('absolute' => TRUE));

  node_feed($nids, $channel);
}
