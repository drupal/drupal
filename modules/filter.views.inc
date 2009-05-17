<?php

/**
 * @file
 * Provide basic views data for filter.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function filter_views_data() {
  // ----------------------------------------------------------------------
  // filter_format table

  // Have not defined $data['filter_formats']['table']['group'] since
  // no fields are defined here yet.
  $data['filter_formats']['moved to'] = 'filter_format';
  $data['filter_format']['table']['join'] = array(
    'node_revision' => array(
      'left_field' => 'format',
      'field' => 'format',
    ),
    'node' => array(
      'left_table' => 'node_revision',
      'left_field' => 'format',
      'field' => 'format',
    ),
  );

  return $data;
}
