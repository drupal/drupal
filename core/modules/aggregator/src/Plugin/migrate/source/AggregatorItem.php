<?php

namespace Drupal\aggregator\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal aggregator item source from database.
 *
 * @MigrateSource(
 *   id = "aggregator_item",
 *   source_module = "aggregator"
 * )
 */
class AggregatorItem extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('aggregator_item', 'ai')
      ->fields('ai')
      ->orderBy('ai.iid');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'iid' => $this->t('Primary Key: Unique ID for feed item.'),
      'fid' => $this->t('The {aggregator_feed}.fid to which this item belongs.'),
      'title' => $this->t('Title of the feed item.'),
      'link' => $this->t('Link to the feed item.'),
      'author' => $this->t('Author of the feed item.'),
      'description' => $this->t('Body of the feed item.'),
      'timestamp' => $this->t('Post date of feed item, as a Unix timestamp.'),
      'guid' => $this->t('Unique identifier for the feed item.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['iid']['type'] = 'integer';
    return $ids;
  }

}
