<?php

/**
 * @file
 * Provides views runtime hooks for views.module.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\PluginBase;

/**
 * Implements hook_views_query_substitutions().
 *
 * Makes the following substitutions:
 * - Current time.
 * - Drupal version.
 * - Special language codes; see
 *   \Drupal\views\Plugin\views\PluginBase::listLanguages().
 */
function views_views_query_substitutions(ViewExecutable $view) {
  $substitutions = array(
    '***CURRENT_VERSION***' => \Drupal::VERSION,
    '***CURRENT_TIME***' => REQUEST_TIME,
  ) + PluginBase::queryLanguageSubstitutions();

  return $substitutions;
}
