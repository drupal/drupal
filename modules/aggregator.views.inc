<?php

/**
 * @file
 * Provide views data and handlers for aggregator.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function aggregator_views_data() {
  // ----------------------------------------------------------------------
  // Main Aggregator Item base table

  // Define the base group of this table. Fields that don't
  // have a group defined will go into this field by default.
  $data['aggregator_item']['table']['group']  = t('Aggregator');

  // Advertise this table as a possible base table
  $data['aggregator_item']['table']['base'] = array(
    'field' => 'iid',
    'title' => t('Aggregator item'),
    'help' => t("Aggregator items are imported from external RSS and Atom news feeds."),
  );

  // ----------------------------------------------------------------
  // Fields

  // item id.
  $data['aggregator_item']['iid'] = array(
    'title' => t('Feed Item ID'),
    'help' => t('The unique ID of the aggregator item.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // iid
  $data['aggregator_item']['iid'] = array(
    'title' => t('Item ID'),
    'help' => t('The unique ID of the aggregator item.'), // The help that appears on the UI,
    // Information for displaying the iid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a iid as an argument
    'argument' => array(
      'handler' => 'views_handler_argument_aggregator_iid',
      'name field' => 'title', // the field to display in the summary.
      'numeric' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // title
  $data['aggregator_item']['title'] = array(
    'title' => t('Title'), // The item it appears as on the UI,
    'help' => t('The title of the aggregator item.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_aggregator_title_link',
      'extra' => array('link'),
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // link
  $data['aggregator_item']['link'] = array(
    'title' => t('Link'), // The item it appears as on the UI,
    'help' => t('The link to the original source URL of the item.'),
    'field' => array(
      'handler' => 'views_handler_field_url',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // author
  $data['aggregator_item']['author'] = array(
    'title' => t('Author'), // The item it appears as on the UI,
    'help' => t('The author of the original imported item.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_aggregator_xss',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // guid
  $data['aggregator_item']['guid'] = array(
    'title' => t('GUID'), // The item it appears as on the UI,
    'help' => t('The guid of the original imported item.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_xss',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_string',
    ),
  );

  // feed body
  $data['aggregator_item']['description'] = array(
    'title' => t('Body'), // The item it appears as on the UI,
    'help' => t('The actual content of the imported item.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_aggregator_xss',
      'click sortable' => FALSE,
     ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // item timestamp
  $data['aggregator_item']['timestamp'] = array(
    'title' => t('Timestamp'), // The item it appears as on the UI,
    'help' => t('The date the original feed item was posted. (With some feeds, this will be the date it was imported.)'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
  );


  // ----------------------------------------------------------------------
  // Aggregator feed table

  $data['aggregator_feed']['table']['group']  = t('Aggregator feed');

  // Explain how this table joins to others.
  $data['aggregator_feed']['table']['join'] = array(
    'aggregator_item' => array(
      'left_field' => 'fid',
      'field' => 'fid',
    ),
  );

  // fid
  $data['aggregator_feed']['fid'] = array(
    'title' => t('Feed ID'),
    'help' => t('The unique ID of the aggregator feed.'), // The help that appears on the UI,
    // Information for displaying the fid
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a fid as an argument
    'argument' => array(
      'handler' => 'views_handler_argument_aggregator_fid',
      'name field' => 'title', // the field to display in the summary.
      'numeric' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    // Information for sorting on a fid.
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // title
  $data['aggregator_feed']['title'] = array(
    'title' => t('Title'), // The item it appears as on the UI,
    'help' => t('The title of the aggregator feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_aggregator_title_link',
      'extra' => array('link'),
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // link
  $data['aggregator_feed']['link'] = array(
    'title' => t('Link'), // The item it appears as on the UI,
    'help' => t('The link to the source URL of the feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_url',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // feed last updated
  $data['aggregator_feed']['checked'] = array(
    'title' => t('Last checked'), // The item it appears as on the UI,
    'help' => t('The date the feed was last checked for new content.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
  );

  // feed description
  $data['aggregator_feed']['description'] = array(
    'title' => t('Description'), // The item it appears as on the UI,
    'help' => t('The description of the aggregator feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_xss',
      'click sortable' => FALSE,
     ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  // feed last updated
  $data['aggregator_feed']['modified'] = array(
    'title' => t('Last modified'), // The item it appears as on the UI,
    'help' => t('The date of the most recent new content on the feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'handler' => 'views_handler_filter_date',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_date',
    ),
  );

  // ----------------------------------------------------------------------
  // Aggregator category feed table

  $data['aggregator_category_feed']['table']['join'] = array(
    'aggregator_item' => array(
      'left_field' => 'fid',
      'field' => 'fid',
    ),
  );

  // ----------------------------------------------------------------------
  // Aggregator category table

  $data['aggregator_category']['table']['group'] = t('Aggregator category');

  $data['aggregator_category']['table']['join'] = array(
    'aggregator_item' => array(
      'left_table' => 'aggregator_category_feed',
      'left_field' => 'cid',
      'field' => 'cid',
    ),
  );

  // cid
  $data['aggregator_category']['cid'] = array(
    'title' => t('Category ID'),
    'help' => t('The unique ID of the aggregator category.'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_aggregator_category_cid',
      'name field' => 'title',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_aggregator_category_cid',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
  );

  // title
  $data['aggregator_category']['title'] = array(
    'title' => t('Category'),
    'help' => t('The title of the aggregator category.'),
    'field' => array(
      'handler' => 'views_handler_field_aggregator_category',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_string',
    ),
  );

  return $data;
}

/**
 * Implements hook_views_plugins().
 */
function aggregator_views_plugins() {
  return array(
    'module' => 'views', // This just tells our themes are elsewhere.
    'row' => array(
      'aggregator_rss' => array(
        'title' => t('Aggregator item'),
        'help' => t('Display the aggregator item using the data from the original source.'),
        'handler' => 'views_plugin_row_aggregator_rss',
        'path' => drupal_get_path('module', 'views') . '/modules/node', // not necessary for most modules
        'theme' => 'views_view_row_rss',
        'base' => array('aggregator_item'), // only works with 'node' as base.
        'uses options' => TRUE,
        'type' => 'feed',
        'help topic' => 'style-aggregator-rss',
      ),
    ),
  );
}
