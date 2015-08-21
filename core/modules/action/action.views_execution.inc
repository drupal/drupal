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
  $select_all = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#attributes' => array('class' => array('action-table-select-all')),
  );
  return array(
    '<!--action-bulk-form-select-all-->' => drupal_render($select_all),
  );
}
