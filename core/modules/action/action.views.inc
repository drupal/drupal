<?php

/**
 * @file
 * Provides views data for action.module.
 */

/**
 * Implements hook_views_data().
 *
 * @todo hook_views_data() is used instead of hook_views_data_alter(), because
 *   the alter hook doesn't load the *.views.inc automatically.
 */
function action_views_data() {
  $data['action']['table']['group'] = t('Action');
  $data['action']['table']['join'] = array(
    '#global' => array(),
  );
  $data['action']['action_bulk_form'] = array(
    'title' => t('Bulk update'),
    'help' => t('Allows users to apply an action to one or more items.'),
    'field' => array(
      'id' => 'action_bulk_form',
    ),
  );
  return $data;
}
