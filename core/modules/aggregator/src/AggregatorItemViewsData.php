<?php

/**
 * @file
 * Contains \Drupal\aggregator\AggregatorItemViewsData.
 */

namespace Drupal\aggregator;

use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the views data for the aggregator item entity type.
 */
class AggregatorItemViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
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

    return $data;
  }

}

