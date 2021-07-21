<?php

/**
 * @file
 * Provide views data for contextual.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function contextual_views_data_alter(&$data) {
  $data['views']['contextual_links'] = [
    'title' => t('Contextual Links'),
    'field' => [
      'help' => t('Display fields in a contextual links menu.'),
      'id' => 'contextual_links',
    ],
    'area' => [
      'help' => t('Display links in a contextual links menu.'),
      'id' => 'contextual_links',
    ],
  ];
}
