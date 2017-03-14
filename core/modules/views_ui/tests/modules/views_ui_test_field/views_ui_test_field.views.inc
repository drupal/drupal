<?php

/**
 * @file
 * Provide views data for testing purposes.
 */

/**
 * Implements hook_views_data().
 */
function views_ui_test_field_views_data() {

  $data['views']['views_test_field_1'] = [
    'title' => t('Views test field 1 - FIELD_1_TITLE'),
    'help' => t('Field 1 for testing purposes - FIELD_1_DESCRIPTION'),
    'field' => [
      'id' => 'views_test_field_1',
    ],
  ];
  $data['views']['views_test_field_2'] = [
    'title' => t('Views test field 2 - FIELD_2_TITLE'),
    'help' => t('Field 2 for testing purposes - FIELD_2_DESCRIPTION'),
    'field' => [
      'id' => 'views_test_field_2',
    ],
  ];

  return $data;
}
