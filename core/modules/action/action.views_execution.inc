<?php

/**
 * @file
 * Provides views runtime hooks for action.module.
 */

use Drupal\Component\Utility\String;

/**
 * Implements hook_views_form_substitutions().
 */
function action_views_form_substitutions() {
  // Views String::checkPlain()s the column label, so we need to match that.
  $select_all_placeholder = String::checkPlain('<!--action-bulk-form-select-all-->');
  $select_all = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#attributes' => array('class' => array('action-table-select-all')),
  );
  return array(
    $select_all_placeholder => drupal_render($select_all),
  );
}
