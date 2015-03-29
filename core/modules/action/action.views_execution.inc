<?php

/**
 * @file
 * Provides views runtime hooks for action.module.
 */

use Drupal\Component\Utility\SafeMarkup;

/**
 * Implements hook_views_form_substitutions().
 */
function action_views_form_substitutions() {
  // Views SafeMarkup::checkPlain()s the column label, so we need to match that.
  $select_all_placeholder = SafeMarkup::checkPlain('<!--action-bulk-form-select-all-->');
  $select_all = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#attributes' => array('class' => array('action-table-select-all')),
  );
  return array(
    $select_all_placeholder => drupal_render($select_all),
  );
}
