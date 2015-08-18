<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\source\d7\Field.
 */

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 field source from database.
 *
 * @MigrateSource(
 *   id = "d7_field"
 * )
 */
class Field extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config', 'fc')
      ->fields('fc')
      ->fields('fci', array('entity_type'))
      ->condition('fc.active', 1)
      ->condition('fc.deleted', 0)
      ->condition('fc.storage_active', 1);
    $query->join('field_config_instance', 'fci', 'fc.id = fci.field_id');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'field_name' => $this->t('The name of this field.'),
      'type' => $this->t('The type of this field.'),
      'module' => $this->t('The module that implements the field type.'),
      'storage' => $this->t('The field storage.'),
      'locked' => $this->t('Locked'),
      'cardinality' => $this->t('Cardinality'),
      'translatable' => $this->t('Translatable'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row, $keep = TRUE) {
    foreach (unserialize($row->getSourceProperty('data')) as $key => $value) {
      $row->setSourceProperty($key, $value);
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'field_name' => array(
        'type' => 'string',
        'alias' => 'fc',
      ),
      'entity_type' => array(
        'type' => 'string',
        'alias' => 'fci',
      ),
    );
  }
}
