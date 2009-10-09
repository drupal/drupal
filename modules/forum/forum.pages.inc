<?php
// $Id: forum.pages.inc,v 1.3 2009/10/09 00:59:59 dries Exp $

/**
 * @file
 * User page callbacks for the forum module.
 */

/**
 * Menu callback; prints a forum listing.
 */
function forum_page($tid = 0) {
  $topics = '';
  $forum_per_page = variable_get('forum_per_page', 25);
  $sortby = variable_get('forum_order', 1);

  $forums = forum_get_forums($tid);
  $parents = taxonomy_get_parents_all($tid);
  if ($tid && !in_array($tid, variable_get('forum_containers', array()))) {
    $topics = forum_get_topics($tid, $sortby, $forum_per_page);
  }

  return theme('forums', array('forums' => $forums, 'topics' => $topics, 'parents' => $parents, 'tid' => $tid, 'sortby' => $sortby, 'forums_per_page' => $forum_per_page));
}
