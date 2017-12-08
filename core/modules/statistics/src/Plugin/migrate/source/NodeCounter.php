<?php

namespace Drupal\statistics\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Node counter source from database.
 *
 * @MigrateSource(
 *   id = "node_counter",
 *   source_module = "statistics"
 * )
 */
class NodeCounter extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('node_counter', 'nc')->fields('nc');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('The node ID.'),
      'totalcount' => $this->t('The total number of times the node has been viewed.'),
      'daycount' => $this->t('The total number of times the node has been viewed today.'),
      'timestamp' => $this->t('The most recent time the node has been viewed.'),
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
