<?php

/**
 * @file
 * Provide views data and handlers for statistics.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function statistics_views_data() {
  // Basic table information.

  // node_counter table

  $data['node_counter']['table']['group']  = t('Content statistics');

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
      'id' => 'numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // daycount
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
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // timestamp
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
    'sort' => array(
      'id' => 'standard',
    ),
  );

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

  // accesslog.aid
  $data['accesslog']['aid'] = array(
    'title' => t('Aid'),
    'help' => t('Unique access event ID.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
      'name field' => 'wid',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // session id
  $data['accesslog']['sid'] = array(
    'title' => t('Session ID'),
    'help' => t('Browser session ID of user that visited page.'),

    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
     ),
     'filter' => array(
      'id' => 'string',
     ),
     'argument' => array(
       'id' => 'string',
     ),
     'sort' => array(
      'id' => 'standard',
     ),
  );

  // title
  $data['accesslog']['title'] = array(
    'title' => t('Page title'),
    'help' => t('Title of page visited.'),

    'field' => array(
      'id' => 'accesslog_path',
      'click sortable' => TRUE,
     ),
     'filter' => array(
      'id' => 'string',
     ),
     'sort' => array(
      'id' => 'standard',
     ),
     'argument' => array(
      'id' => 'standard',
     ),
  );

  // path
  $data['accesslog']['path'] = array(
    'title' => t('Path'),
    'help' => t('Internal path to page visited (relative to Drupal root.)'),

    'field' => array(
      'id' => 'accesslog_path',
      'click sortable' => TRUE,
     ),
     'filter' => array(
       'id' => 'string',
     ),
     'sort' => array(
       'id' => 'standard',
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
      'id' => 'url',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // hostname
  $data['accesslog']['hostname'] = array(
    'title' => t('Hostname'),
    'help' => t('Hostname of user that visited the page.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // user
  $data['accesslog']['uid'] = array(
    'title' => t('User'),
    'help' => t('The user who visited the site.'),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'base field' => 'uid',
     ),
  );

  // timer
  $data['accesslog']['timer'] = array(
    'title' => t('Timer'),
    'help' => t('Time in milliseconds that the page took to load.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // timestamp
  $data['accesslog']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('Timestamp of when the page was visited.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
