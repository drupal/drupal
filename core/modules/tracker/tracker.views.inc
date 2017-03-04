<?php

/**
 * @file
 * Provide views data for tracker.module.
 */

/**
 * Implements hook_views_data().
 */
function tracker_views_data() {
  $data = [];

  $data['tracker_node']['table']['group'] = t('Tracker');
  $data['tracker_node']['table']['join'] = [
    'node_field_data' => [
      'type' => 'INNER',
      'left_field' => 'nid',
      'field' => 'nid',
    ],
  ];
  $data['tracker_node']['nid'] = [
    'title' => t('Nid'),
    'help' => t('The node ID of the node.'),
    'field' => [
      'id' => 'node',
    ],
    'argument' => [
      'id' => 'node_nid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'nid',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['tracker_node']['published'] = [
    'title' => t('Published'),
    'help' => t('Whether or not the node is published.'),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Published'),
      'type' => 'yes-no',
      'accept null' => TRUE,
      'use_equal' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['tracker_node']['changed'] = [
    'title' => t('Updated date'),
    'help' => t('The date the node was last updated.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  $data['tracker_user']['table']['group'] = t('Tracker - User');
  $data['tracker_user']['table']['join'] = [
    'node_field_data' => [
      'type' => 'INNER',
      'left_field' => 'nid',
      'field' => 'nid',
    ],
    'user_field_data' => [
      'type' => 'INNER',
      'left_field' => 'uid',
      'field' => 'uid',
    ],
  ];
  $data['tracker_user']['nid'] = [
    'title' => t('Nid'),
    'help' => t('The node ID of the node a user created or commented on. You must use an argument or filter on UID or you will get misleading results using this field.'),
    'field' => [
      'id' => 'node',
    ],
    'argument' => [
      'id' => 'node_nid',
      'name field' => 'title',
      'numeric' => TRUE,
      'validate type' => 'nid',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['tracker_user']['uid'] = [
    'title' => t('Uid'),
    'help' => t('The user ID of a user who touched the node (either created or commented on it).'),
    'field' => [
      'id' => 'user_name',
    ],
    'argument' => [
      'id' => 'user_uid',
      'name field' => 'name',
    ],
    'filter' => [
      'title' => t('Name'),
      'id' => 'user_name',
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['tracker_user']['published'] = [
    'title' => t('Published'),
    'help' => t('Whether or not the node is published. You must use an argument or filter on UID or you will get misleading results using this field.'),
    'field' => [
      'id' => 'boolean',
    ],
    'filter' => [
      'id' => 'boolean',
      'label' => t('Published'),
      'type' => 'yes-no',
      'accept null' => TRUE,
      'use_equal' => TRUE,
    ],
    'sort' => [
      'id' => 'standard',
    ],
  ];
  $data['tracker_user']['changed'] = [
    'title' => t('Updated date'),
    'help' => t('The date the node was last updated or commented on. You must use an argument or filter on UID or you will get misleading results using this field.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function tracker_views_data_alter(&$data) {
  // Provide additional uid_touch handlers which are handled by tracker
  $data['node_field_data']['uid_touch_tracker'] = [
    'group' => t('Tracker - User'),
    'title' => t('User posted or commented'),
    'help' => t('Display nodes only if a user posted the node or commented on the node.'),
    'argument' => [
      'field' => 'uid',
      'name table' => 'users_field_data',
      'name field' => 'name',
      'id' => 'tracker_user_uid',
      'no group by' => TRUE,
    ],
    'filter' => [
      'field' => 'uid',
      'name table' => 'users_field_data',
      'name field' => 'name',
      'id' => 'tracker_user_uid'
    ],
  ];
}
