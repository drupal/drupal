<?php

/**
 * @file
 * Contains \Drupal\aggregator\AggregatorFeedViewsData.
 */

namespace Drupal\aggregator;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the aggregator feed entity type.
 */
class AggregatorFeedViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['aggregator_feed']['table']['join'] = array(
      'aggregator_item' => array(
        'left_field' => 'fid',
        'field' => 'fid',
      ),
    );

    $data['aggregator_feed']['fid']['help'] = $this->t('The unique ID of the aggregator feed.');
    $data['aggregator_feed']['fid']['argument']['id'] = 'aggregator_fid';
    $data['aggregator_feed']['fid']['argument']['name field'] = 'title';
    $data['aggregator_feed']['fid']['argument']['numeric'] = TRUE;

    $data['aggregator_feed']['fid']['filter']['id'] = 'numeric';

    $data['aggregator_feed']['title']['help'] = $this->t('The title of the aggregator feed.');
    $data['aggregator_feed']['title']['field']['default_formatter'] = 'aggregator_title';

    $data['aggregator_feed']['argument']['id'] = 'string';

    $data['aggregator_feed']['url']['help'] = $this->t('The fully-qualified URL of the feed.');

    $data['aggregator_feed']['link']['help'] = $this->t('The link to the source URL of the feed.');

    $data['aggregator_feed']['checked']['help'] = $this->t('The date the feed was last checked for new content.');

    $data['aggregator_feed']['description']['help'] = $this->t('The description of the aggregator feed.');
    $data['aggregator_feed']['description']['field']['click sortable'] = FALSE;

    $data['aggregator_feed']['modified']['help'] = $this->t('The date of the most recent new content on the feed.');

    return $data;
  }

}
