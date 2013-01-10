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
