<?php

/**
 * @file
 * Provides views data and handlers for action.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 *
 * @todo hook_views_data() is used instead of hook_views_data_alter(), because
 *   the alter hook doesn't load the *.views.inc automatically.
 */
function action_views_data() {
  $data['views']['action_bulk_form'] = array(
    'title' => t('Actions bulk form'),
    'help' => t('Add a form element that lets you apply actions to multiple items.'),
    'field' => array(
      'id' => 'action_bulk_form',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_form_substitutions().
 */
function action_views_form_substitutions() {
  // Views check_plains the column label, so by doing it matches for the
  // replacement.
  $select_all_placeholder = check_plain('<!--action-bulk-form-select-all-->');
  $select_all = array(
    '#type' => 'checkbox',
    '#default_value' => FALSE,
    '#attributes' => array('class' => array('action-table-select-all')),
  );

  return array(
    $select_all_placeholder => drupal_render($select_all),
  );
}
