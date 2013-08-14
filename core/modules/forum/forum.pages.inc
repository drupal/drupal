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
  $config = Drupal::config('forum.settings');
  $vocabulary = entity_load('taxonomy_vocabulary', $config->get('vocabulary'));

  if (!isset($forum_term)) {
    // On the main page, display all the top-level forums.
    $forum_term = forum_forum_load(0);
    // Set the page title to forum's vocabulary name.
    drupal_set_title($vocabulary->label());
  }

  if ($forum_term->id() && array_search($forum_term->id(), $config->get('containers')) === FALSE) {
    // Add RSS feed for forums.
    drupal_add_feed('taxonomy/term/' . $forum_term->id() . '/feed', 'RSS - ' . $forum_term->label());
  }

  if (empty($forum_term->forums) && empty($forum_term->parents)) {
    // Root of empty forum.
    drupal_set_title(t('No forums defined'));
  }

  $forum_per_page = $config->get('topics.page_limit');
  $sort_by = $config->get('topics.order');

  if (empty($forum_term->container)) {
    $topics = forum_get_topics($forum_term->id(), $sort_by, $forum_per_page);
  }
  else {
    $topics = '';
  }

  $build = array(
    '#theme' => 'forums',
    '#forums' => $forum_term->forums,
    '#topics' => $topics,
    '#parents' => $forum_term->parents,
    '#tid' => $forum_term->id(),
    '#sortby' => $sort_by,
    '#forums_per_page' => $forum_per_page,
  );
  $build['#attached']['css'][] = drupal_get_path('module', 'forum') . '/css/forum.module.css';
  return $build;
}
