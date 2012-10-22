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

  // Fields

  // iid
  $data['aggregator_item']['iid'] = array(
    'title' => t('Item ID'),
    'help' => t('The unique ID of the aggregator item.'), // The help that appears on the UI,
    // Information for displaying the iid
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a iid as an argument
    'argument' => array(
      'id' => 'aggregator_iid',
      'name field' => 'title', // the field to display in the summary.
      'numeric' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'id' => 'numeric',
    ),
    // Information for sorting on a nid.
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // title
  $data['aggregator_item']['title'] = array(
    'title' => t('Title'), // The item it appears as on the UI,
    'help' => t('The title of the aggregator item.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'aggregator_title_link',
      'extra' => array('link'),
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'string',
    ),
  );

  // link
  $data['aggregator_item']['link'] = array(
    'title' => t('Link'), // The item it appears as on the UI,
    'help' => t('The link to the original source URL of the item.'),
    'field' => array(
      'id' => 'url',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'string',
    ),
  );

  // author
  $data['aggregator_item']['author'] = array(
    'title' => t('Author'), // The item it appears as on the UI,
    'help' => t('The author of the original imported item.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'aggregator_xss',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // guid
  $data['aggregator_item']['guid'] = array(
    'title' => t('GUID'), // The item it appears as on the UI,
    'help' => t('The guid of the original imported item.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'xss',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // feed body
  $data['aggregator_item']['description'] = array(
    'title' => t('Body'), // The item it appears as on the UI,
    'help' => t('The actual content of the imported item.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'aggregator_xss',
      'click sortable' => FALSE,
     ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'string',
    ),
  );

  // item timestamp
  $data['aggregator_item']['timestamp'] = array(
    'title' => t('Timestamp'), // The item it appears as on the UI,
    'help' => t('The date the original feed item was posted. (With some feeds, this will be the date it was imported.)'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'date',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
  );

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
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    // Information for accepting a fid as an argument
    'argument' => array(
      'id' => 'aggregator_fid',
      'name field' => 'title', // the field to display in the summary.
      'numeric' => TRUE,
    ),
    // Information for accepting a nid as a filter
    'filter' => array(
      'id' => 'numeric',
    ),
    // Information for sorting on a fid.
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // title
  $data['aggregator_feed']['title'] = array(
    'title' => t('Title'), // The item it appears as on the UI,
    'help' => t('The title of the aggregator feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'aggregator_title_link',
      'extra' => array('link'),
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  // link
  $data['aggregator_feed']['link'] = array(
    'title' => t('Link'), // The item it appears as on the UI,
    'help' => t('The link to the source URL of the feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'url',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  // feed last updated
  $data['aggregator_feed']['checked'] = array(
    'title' => t('Last checked'), // The item it appears as on the UI,
    'help' => t('The date the feed was last checked for new content.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
  );

  // feed description
  $data['aggregator_feed']['description'] = array(
    'title' => t('Description'), // The item it appears as on the UI,
    'help' => t('The description of the aggregator feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'xss',
      'click sortable' => FALSE,
     ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  // feed last updated
  $data['aggregator_feed']['modified'] = array(
    'title' => t('Last modified'), // The item it appears as on the UI,
    'help' => t('The date of the most recent new content on the feed.'),
     // Information for displaying a title as a field
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'date',
    ),
    // Information for accepting a title as a filter
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
  );

  // Aggregator category feed table

  $data['aggregator_category_feed']['table']['join'] = array(
    'aggregator_item' => array(
      'left_field' => 'fid',
      'field' => 'fid',
    ),
  );

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
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'argument' => array(
      'id' => 'aggregator_category_cid',
      'name field' => 'title',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'aggregator_category_cid',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // title
  $data['aggregator_category']['title'] = array(
    'title' => t('Category'),
    'help' => t('The title of the aggregator category.'),
    'field' => array(
      'id' => 'aggregator_category',
      'click sortable' => TRUE,
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  return $data;
}
