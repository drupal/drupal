<?php

/**
 * @file
 * Contains \Drupal\aggregator\Plugin\migrate\source\d6\AggregatorFeed.
 */

namespace Drupal\aggregator\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 feed source from database.
 *
 * @MigrateSource(
 *   id = "d6_aggregator_feed",
 *   source_provider = "aggregator"
 * )
 */
class AggregatorFeed extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('aggregator_feed', 'af')
      ->fields('af', array(
        'fid',
        'title',
        'url',
        'refresh',
        'checked',
        'link',
        'description',
        'image',
        'etag',
        'modified',
        'block',
      ));

    $query->orderBy('fid');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'fid' => $this->t('The feed ID.'),
      'title' => $this->t('Title of the feed.'),
      'url' => $this->t('URL to the feed.'),
      'refresh' => $this->t('Refresh frequency in seconds.'),
      'checked' => $this->t('Last-checked unix timestamp.'),
      'link' => $this->t('Parent website of feed.'),
      'description' => $this->t('Parent website\'s description fo the feed.'),
      'image' => $this->t('An image representing the feed.'),
      'etag' => $this->t('Entity tag HTTP response header.'),
      'modified' => $this->t('When the feed was last modified.'),
      'block' => $this->t("Number of items to display in the feed's block."),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

}
