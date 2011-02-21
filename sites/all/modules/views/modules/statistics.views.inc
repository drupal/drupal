<?php
// $Id: statistics.views.inc,v 1.9.6.3 2010/07/19 09:18:42 dereine Exp $
/**
 * @file
 * Provide views data and handlers for statistics.module
 */

/**
 * @defgroup views_statistics_module statistics.module handlers
 *
 * Includes the ability to create views of just the statistics table.
 * @{
 */

/**
 * Implements hook_views_data()
 */
function statistics_views_data() {
  // Basic table information.

  // ----------------------------------------------------------------
  // node_counter table

  $data['node_counter']['table']['group']  = t('Node statistics');

  $data['node_counter']['table']['join'] = array(
    // ...to the node table
    'node' => array(
      'left_field' => 'nid',
      'field' => 'nid',
    ),
  );

  // totalcount
  $data['node_counter']['totalcount'] = array(
    'title' => t('Total views'),
    'help' => t('The total number of times the node has been viewed.'),

    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // daycount
  $data['node_counter']['daycount'] = array(
    'title' => t('Views today'),
    'help' => t('The total number of times the node has been viewed today.'),

    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // timestamp
  $data['node_counter']['timestamp'] = array(
    'title' => t('Most recent view'),
    'help' => t('The most recent time the node has been viewed.'),

    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );


  // ----------------------------------------------------------------
  // accesslog table

  $data['accesslog']['table']['group']  = t('Access log');

  // Advertise this table as a possible base table
  $data['accesslog']['table']['base'] = array(
    'field' => 'aid',
    'title' => t('Access log'),
    'help' => t('Stores site access information.'),
    'weight' => 10,
  );

  // For other base tables, explain how we join
  $data['accesslog']['table']['join'] = array(
    'users' => array(
      'field' => 'uid',
      'left_field' => 'uid',
     ),
  );

  // session id
  $data['accesslog']['sid'] = array(
    'title' => t('Session ID'),
    'help' => t('Browser session ID of user that visited page.'),

    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
     ),
     'filter' => array(
      'handler' => 'views_handler_filter_string',
     ),
     'argument' => array(
       'handler' => 'views_handler_argument_string',
     ),
     'sort' => array(
      'handler' => 'views_handler_sort',
     ),
  );

  // title
  $data['accesslog']['title'] = array(
    'title' => t('Page title'),
    'help' => t('Title of page visited.'),

    'field' => array(
      'handler' => 'views_handler_field_accesslog_path',
      'click sortable' => TRUE,
     ),
     'filter' => array(
      'handler' => 'views_handler_filter_string',
     ),
     'sort' => array(
      'handler' => 'views_handler_sort',
     ),
     'argument' => array(
      'handler' => 'views_handler_argument_string',
     ),
  );

  // path
  $data['accesslog']['path'] = array(
    'title' => t('Path'),
    'help' => t('Internal path to page visited (relative to Drupal root.)'),

    'field' => array(
      'handler' => 'views_handler_field_accesslog_path',
      'click sortable' => TRUE,
     ),
     'filter' => array(
       'handler' => 'views_handler_filter_string',
     ),
     'sort' => array(
       'handler' => 'views_handler_sort',
     ),
     //No argument here.  Can't send forward slashes as arguments.
     //Can be worked around by node ID.
     //(but what about aliases?)
  );

  // referrer
  $data['accesslog']['url'] = array(
    'title' => t('Referrer'),
    'help' => t('Referrer URI.'),
    'field' => array(
      'handler' => 'views_handler_field_url',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // hostname
  $data['accesslog']['hostname'] = array(
    'title' => t('Hostname'),
    'help' => t('Hostname of user that visited the page.'),
    'field' => array(
      'handler' => 'views_handler_field',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // user
  $data['accesslog']['uid'] = array(
    'title' => t('User'),
    'help' => t('The user who visited the site.'),
    'relationship' => array(
      'handler' => 'views_handler_relationship',
      'base' => 'users',
      'base field' => 'uid',
     ),
  );

  // timer
  $data['accesslog']['timer'] = array(
    'title' => t('Timer'),
    'help' => t('Time in milliseconds that the page took to load.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // timestamp
  $data['accesslog']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('Timestamp of when the page was visited.'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
  );


  return $data;
}

/**
 * @}
 */
