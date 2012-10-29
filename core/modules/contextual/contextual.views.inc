<?php

/**
 * @file
 * Provide views data and handlers for contextual.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data_alter().
 */
function contextual_views_data_alter(&$data) {
  $data['views']['contextual_links'] = array(
    'title' => t('Contextual Links'),
    'help' => t('Display fields in a contextual links menu.'),
    'field' => array(
      'id' => 'contextual_links',
    ),
  );
}
