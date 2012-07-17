<?php

/**
 * @file
 * User page callbacks for the Forum module.
 */

/**
 * Page callback: Prints a forum listing.
 *
 * @param $forum_term
 *   A tree of all forums for a given taxonomy term ID. Defaults to NULL. See
 *   the return object of forum_forum_load() for a complete definition.
 *
 * @return
 *   A string containing HTML representing the themed forum listing.
 *
 * @see forum_menu()
 */
function forum_page($forum_term = NULL) {
  if (!isset($forum_term)) {
    // On the main page, display all the top-level forums.
    $forum_term = forum_forum_load(0);
  }

  $forum_per_page = variable_get('forum_per_page', 25);
  $sortby = variable_get('forum_order', 1);

  if (empty($forum_term->container)) {
    $topics = forum_get_topics($forum_term->tid, $sortby, $forum_per_page);
  }
  else {
    $topics = '';
  }

  return theme('forums', array('forums' => $forum_term->forums, 'topics' => $topics, 'parents' => $forum_term->parents, 'tid' => $forum_term->tid, 'sortby' => $sortby, 'forums_per_page' => $forum_per_page));
}
