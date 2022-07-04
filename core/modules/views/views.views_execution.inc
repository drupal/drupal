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
  $substitutions = [
    '***CURRENT_VERSION***' => \Drupal::VERSION,
    '***CURRENT_TIME***' => \Drupal::time()->getRequestTime(),
  ] + PluginBase::queryLanguageSubstitutions();

  return $substitutions;
}

/**
 * Implements hook_views_form_substitutions().
 */
function views_views_form_substitutions() {
  $select_all = [
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#attributes' => ['class' => ['action-table-select-all']],
  ];
  return [
    '<!--action-bulk-form-select-all-->' => \Drupal::service('renderer')->render($select_all),
  ];
}
