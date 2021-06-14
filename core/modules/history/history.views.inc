<?php

/**
 * @file
 * Provide views data for history.module.
 */

/**
 * Implements hook_views_data().
 */
function history_views_data() {
  // History table

  // We're actually defining a specific instance of the table, so let's
  // alias it so that we can later add the real table for other purposes if we
  // need it.
  $data['history']['table']['group'] = t('Content');

  // Explain how this table joins to others.
  $data['history']['table']['join'] = [
     // Directly links to node table.
    'node_field_data' => [
      'table' => 'history',
      'left_field' => 'nid',
      'field' => 'nid',
      'extra' => [
        ['field' => 'uid', 'value' => '***CURRENT_USER***', 'numeric' => TRUE],
      ],
    ],
  ];

  $data['history']['timestamp'] = [
    'title' => t('Has new content'),
    'field' => [
      'id' => 'history_user_timestamp',
      'help' => t('Show a marker if the content is new or updated.'),
    ],
    'filter' => [
      'help' => t('Show only content that is new or updated.'),
      'id' => 'history_user_timestamp',
    ],
  ];

  return $data;
}
