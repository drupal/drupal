<?php

namespace Drupal\migrate_drupal\Plugin\migrate\source\d8;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal config source from database.
 *
 * @MigrateSource(
 *   id = "d8_config",
 *   source_module = "system",
 * )
 */
class Config extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('config', 'c')
      ->fields('c', ['collection', 'name', 'data']);
    if (!empty($this->configuration['collections'])) {
      $query->condition('collection', (array) $this->configuration['collections'], 'IN');
    }
    if (!empty($this->configuration['names'])) {
      $query->condition('name', (array) $this->configuration['names'], 'IN');
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'collection' => $this->t('The config object collection.'),
      'name' => $this->t('The config object name.'),
      'data' => $this->t('Serialized configuration object data.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['collection']['type'] = 'string';
    $ids['name']['type'] = 'string';
    return $ids;
  }

}
