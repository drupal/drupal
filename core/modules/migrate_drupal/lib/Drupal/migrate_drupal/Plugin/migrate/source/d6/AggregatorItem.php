<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\source\d6\AggregatorItem.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 aggregator item source from database.
 *
 * @MigrateSource(
 *   id = "d6_aggregator_item",
 *   source_provider = "aggregator"
 * )
 */
class AggregatorItem extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('aggregator_item', 'ai')
      ->fields('ai', array('iid', 'fid', 'title', 'link', 'author',
        'description', 'timestamp', 'guid'))
      ->orderBy('iid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'iid' => $this->t('Primary Key: Unique ID for feed item.'),
      'fid' => $this->t('The {aggregator_feed}.fid to which this item belongs.'),
      'title' => $this->t('Title of the feed item.'),
      'link' => $this->t('Link to the feed item.'),
      'author' => $this->t('Author of the feed item.'),
      'description' => $this->t('Body of the feed item.'),
      'timestamp' => $this->t('Post date of feed item, as a Unix timestamp.'),
      'guid' => $this->t('Unique identifier for the feed item.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['iid']['type'] = 'integer';
    return $ids;
  }

}
