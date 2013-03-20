<?php

/**
 * @file
 * Provides views data and hooks for views_test_data module.
 */

use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_analyze().
 */
function views_test_data_views_analyze(ViewExecutable $view) {
  $ret = array();

  $ret[] = Analyzer::formatMessage(t('Test ok message'), 'ok');
  $ret[] = Analyzer::formatMessage(t('Test warning message'), 'warning');
  $ret[] = Analyzer::formatMessage(t('Test error message'), 'error');

  return $ret;
}

/**
 * Implements hook_views_data().
 */
function views_test_data_views_data() {
  $state = Drupal::service('state');
  // We use a state variable to keep track of how many times this function is
  // called so we can assert that calls to
  // \Drupal\views\ViewsDataCache::delete() trigger a rebuild of views data.
  if (!($count = $state->get('views_test_data_views_data_count'))) {
    $count = 0;
  }
  $count++;
  $state->set('views_test_data_views_data_count', $count);

  return $state->get('views_test_data_views_data');
}

/**
 * Implements hook_views_data_alter().
 */
function views_test_data_views_data_alter(&$data) {

}

/**
 * Implements hook_views_query_substitutions().
 */
function views_test_data_views_query_substitutions() {

}

/**
 * Implements hook_views_form_substitutions().
 */
function views_test_data_views_form_substitutions() {

}

/**
 * Implements hook_field_views_data().
 */
function views_test_data_field_views_data($field) {

}

/**
 * Implements hook_field_views_data_alter().
 */
function views_test_data_field_views_data_alter(&$data, $field, $module) {

}
