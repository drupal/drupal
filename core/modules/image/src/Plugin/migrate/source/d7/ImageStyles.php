<?php

namespace Drupal\image\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal image styles source from database.
 *
 * @MigrateSource(
 *   id = "d7_image_styles",
 *   source_module = "image"
 * )
 */
class ImageStyles extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('image_styles', 'ims')
      ->fields('ims');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'isid' => $this->t('The primary identifier for an image style.'),
      'name' => $this->t('The style machine name.'),
      'label' => $this->t('The style administrative name.'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['isid']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $effects = [];

    $results = $this->select('image_effects', 'ie')
      ->fields('ie')
      ->condition('isid', $row->getSourceProperty('isid'))
      ->execute();

    foreach ($results as $key => $result) {
      $result['data'] = unserialize($result['data']);
      $effects[$key] = $result;
    }

    $row->setSourceProperty('effects', $effects);
    return parent::prepareRow($row);
  }

}
