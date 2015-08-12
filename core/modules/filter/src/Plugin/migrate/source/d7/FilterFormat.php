<?php

/**
 * @file
 * Contains \Drupal\filter\Plugin\migrate\source\d7\FilterFormat.
 */

namespace Drupal\filter\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal 7 filter source from database.
 *
 * @MigrateSource(
 *   id = "d7_filter_format"
 * )
 */
class FilterFormat extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('filter_format', 'f')->fields('f');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'format' => $this->t('Format ID.'),
      'name' => $this->t('The name of the format.'),
      'cache' => $this->t('Whether the format is cacheable.'),
      'status' => $this->t('The status of the format'),
      'weight' => $this->t('The weight of the format'),
      'filters' => $this->t('The filters configured for the format.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Find filters for this format.
    $filters = $this->select('filter', 'f')
      ->fields('f')
      ->condition('format', $row->getSourceProperty('format'))
      ->condition('status', 1)
      ->execute()
      ->fetchAllAssoc('name');

    foreach ($filters as $id => $filter) {
      $filters[$id]['settings'] = unserialize($filter['settings']);
    }
    $row->setSourceProperty('filters', $filters);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['format']['type'] = 'string';
    return $ids;
  }

}
