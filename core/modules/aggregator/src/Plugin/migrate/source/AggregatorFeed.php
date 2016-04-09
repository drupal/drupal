<?php

namespace Drupal\aggregator\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal feed source from database.
 *
 * @MigrateSource(
 *   id = "aggregator_feed",
 *   source_provider = "aggregator"
 * )
 */
class AggregatorFeed extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('aggregator_feed', 'af')
      ->fields('af');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'fid' => $this->t('The feed ID.'),
      'title' => $this->t('Title of the feed.'),
      'url' => $this->t('URL to the feed.'),
      'refresh' => $this->t('Refresh frequency in seconds.'),
      'checked' => $this->t('Last-checked unix timestamp.'),
      'link' => $this->t('Parent website of the feed.'),
      'description' => $this->t("Parent website's description of the feed."),
      'image' => $this->t('An image representing the feed.'),
      'etag' => $this->t('Entity tag HTTP response header.'),
      'modified' => $this->t('When the feed was last modified.'),
      'block' => $this->t("Number of items to display in the feed's block."),
    );
    if ($this->getModuleSchemaVersion('system') >= 7000) {
      $fields['queued'] = $this->t('Time when this feed was queued for refresh, 0 if not queued.');
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

}
