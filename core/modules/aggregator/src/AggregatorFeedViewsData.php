<?php

/**
 * @file
 * Contains \Drupal\aggregator\AggregatorFeedViewsData.
 */

namespace Drupal\aggregator;

use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the views data for the aggregator feed entity type.
 */
class AggregatorFeedViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = array();

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

}

