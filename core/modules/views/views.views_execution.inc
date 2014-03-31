<?php

/**
 * @file
 * Provides views runtime hooks for views.module.
 */

use Drupal\Core\Language\Language;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 *
 * Substitute current time; this works with cached queries.
 */
function views_views_query_substitutions(ViewExecutable $view) {
  return array(
    '***CURRENT_VERSION***' => \Drupal::VERSION,
    '***CURRENT_TIME***' => REQUEST_TIME,
    '***CURRENT_LANGUAGE***' => \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT)->id,
    '***DEFAULT_LANGUAGE***' => \Drupal::languageManager()->getDefaultLanguage()->id,
  );
}
