<?php

/**
 * @file
 * User page callbacks for the forum module.
 */

/**
 * Menu callback; prints a forum listing.
 */
function forum_page($tid = 0) {
  if (!is_numeric($tid)) {
    return MENU_NOT_FOUND;
  }
  $tid = (int)$tid;

  $topics = '';
  $forum_per_page = variable_get('forum_per_page', 25);
  $sortby = variable_get('forum_order', 1);

  $forums = forum_get_forums($tid);
  $parents = taxonomy_get_parents_all($tid);
  if ($tid && !in_array($tid, variable_get('forum_containers', array()))) {
    $topics = forum_get_topics($tid, $sortby, $forum_per_page);
  }

  return theme('forums', $forums, $topics, $parents, $tid, $sortby, $forum_per_page);
}
