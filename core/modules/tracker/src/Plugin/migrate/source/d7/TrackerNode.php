<?php
/**
 * @file
 * Contains \Drupal\tracker\Plugin\migrate\source\d7\TrackerNode.
 */

namespace Drupal\tracker\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 tracker node source from database.
 *
 * @MigrateSource(
 *   id = "d7_tracker_node",
 *   source_provider = "tracker"
 * )
 */
class TrackerNode extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('tracker_node', 'tn')->fields('tn');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('The {node}.nid this record tracks.'),
      'published' => $this->t('Boolean indicating whether the node is published.'),
      'changed' => $this->t('The Unix timestamp when the node was most recently saved or commented on.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

}
