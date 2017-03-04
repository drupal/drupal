<?php

/**
 * @file
 * Provides views runtime hooks for action.module.
 */

/**
 * Implements hook_views_form_substitutions().
 */
function action_views_form_substitutions() {
  $select_all = [
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#attributes' => ['class' => ['action-table-select-all']],
  ];
  return [
    '<!--action-bulk-form-select-all-->' => drupal_render($select_all),
  ];
}
