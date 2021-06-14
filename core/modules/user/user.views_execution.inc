<?php

/**
 * @file
 * Provide views runtime hooks for user.module.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 *
 * Allow replacement of current user ID so we can cache these queries.
 */
function user_views_query_substitutions(ViewExecutable $view) {
  return ['***CURRENT_USER***' => \Drupal::currentUser()->id()];
}
