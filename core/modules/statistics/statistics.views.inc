<?php

/**
 * @file
 * Provide views data for statistics.module.
 */

/**
 * Implements hook_views_data().
 */
function statistics_views_data() {
  $data['node_counter']['table']['group']  = t('Content statistics');

  $data['node_counter']['table']['join'] = array(
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  $data['node_counter']['totalcount'] = array(
    'title' => t('Total views'),
    'help' => t('The total number of times the node has been viewed.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['node_counter']['daycount'] = array(
    'title' => t('Views today'),
    'help' => t('The total number of times the node has been viewed today.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['node_counter']['timestamp'] = array(
    'title' => t('Most recent view'),
    'help' => t('The most recent time the node has been viewed.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}
