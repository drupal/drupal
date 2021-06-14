<?php

/**
 * @file
 * Provide views runtime hooks for content_moderation.module.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function content_moderation_views_query_substitutions(ViewExecutable $view) {
  $account = \Drupal::currentUser();
  return [
    '***VIEW_ANY_UNPUBLISHED_NODES***' => intval($account->hasPermission('view any unpublished content')),
  ];
}
