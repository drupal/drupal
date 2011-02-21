<?php
// $Id: filter.views.inc,v 1.1.6.2 2010/07/19 09:18:42 dereine Exp $
/**
 * @file
 * Provide basic views data for filter.module.
 */

/**
 * @defgroup views_filter_module filter.module handlers
 *
 * Only includes the table 'filter_formats'.
 * @{
 */

/**
 * Implements hook_views_data()
 */
function filter_views_data() {
  // ----------------------------------------------------------------------
  // filter_formats table

  // Have not defined $data['filter_formats']['table']['group'] since
  // no fields are defined here yet.
  $data['filter_formats']['table']['join'] = array(
    'node_revisions' => array(
      'left_field' => 'format',
      'field' => 'format',
    ),
    'node' => array(
      'left_table' => 'node_revisions',
      'left_field' => 'format',
      'field' => 'format',
    ),
  );

  return $data;
}

/**
 * @}
 */
