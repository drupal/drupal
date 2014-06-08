<?php

/**
 * @file
 * Provides views data for aggregator.module.
 */

/**
 * Implements hook_views_data().
 */
function aggregator_views_data() {
  $data = array();

  $data['aggregator_item']['table']['group'] = t('Aggregator');

  $data['aggregator_item']['table']['base'] = array(
    'field' => 'iid',
    'title' => t('Aggregator item'),
    'help' => t('Aggregator items are imported from external RSS and Atom news feeds.'),
  );
  $data['aggregator_item']['table']['entity type'] = 'aggregator_item';

  $data['aggregator_item']['iid'] = array(
    'title' => t('Item ID'),
    'help' => t('The unique ID of the aggregator item.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'aggregator_iid',
      'name field' => 'title',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['aggregator_item']['title'] = array(
    'title' => t('Title'),
    'help' => t('The title of the aggregator item.'),
    'field' => array(
      'id' => 'aggregator_title_link',
      'extra' => array('link'),
     ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['aggregator_item']['link'] = array(
    'title' => t('Link'),
    'help' => t('The link to the original source URL of the item.'),
    'field' => array(
      'id' => 'url',
     ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['aggregator_item']['author'] = array(
    'title' => t('Author'),
    'help' => t('The author of the original imported item.'),
    'field' => array(
      'id' => 'aggregator_xss',
     ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['aggregator_item']['guid'] = array(
    'title' => t('GUID'),
    'help' => t('The guid of the original imported item.'),
    'field' => array(
      'id' => 'standard',
     ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['aggregator_item']['description'] = array(
    'title' => t('Body'),
    'help' => t('The actual content of the imported item.'),
    'field' => array(
      'id' => 'aggregator_xss',
      'click sortable' => FALSE,
     ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['aggregator_item']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('The date the original feed item was posted. (With some feeds, this will be the date it was imported.)'),
    'field' => array(
      'id' => 'date',
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

  $data['aggregator_feed']['table']['group']  = t('Aggregator feed');

  $data['aggregator_feed']['table']['base'] = array(
    'field' => 'fid',
    'title' => t('Aggregator feed'),
  );

  $data['aggregator_feed']['table']['entity type'] = 'aggregator_feed';

  $data['aggregator_feed']['table']['join'] = array(
    'aggregator_item' => array(
      'left_field' => 'fid',
      'field' => 'fid',
    ),
  );

  $data['aggregator_feed']['fid'] = array(
    'title' => t('Feed ID'),
    'help' => t('The unique ID of the aggregator feed.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'aggregator_fid',
      'name field' => 'title',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['aggregator_feed']['title'] = array(
    'title' => t('Title'),
    'help' => t('The title of the aggregator feed.'),
    'field' => array(
      'id' => 'aggregator_title_link',
      'extra' => array('link'),
     ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['aggregator_feed']['url'] = array(
    'title' => t('URL'),
    'help' => t('The fully-qualified URL of the feed.'),
    'field' => array(
      'id' => 'url',
     ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['aggregator_feed']['link'] = array(
    'title' => t('Link'),
    'help' => t('The link to the source URL of the feed.'),
    'field' => array(
      'id' => 'url',
     ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['aggregator_feed']['checked'] = array(
    'title' => t('Last checked'),
    'help' => t('The date the feed was last checked for new content.'),
    'field' => array(
      'id' => 'date',
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

  $data['aggregator_feed']['description'] = array(
    'title' => t('Description'),
    'help' => t('The description of the aggregator feed.'),
    'field' => array(
      'id' => 'xss',
      'click sortable' => FALSE,
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

  $data['aggregator_feed']['modified'] = array(
    'title' => t('Last modified'),
    'help' => t('The date of the most recent new content on the feed.'),
    'field' => array(
      'id' => 'date',
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

  return $data;
}
