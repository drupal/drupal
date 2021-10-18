<?php

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 field source from database.
 *
 * If the Drupal 7 Title module is enabled, the fields it provides are not
 * migrated. The values of those fields will be migrated to the base fields they
 * were replacing.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_field",
 *   source_module = "field_sql_storage"
 * )
 */
class Field extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config', 'fc')
      ->distinct()
      ->fields('fc')
      ->fields('fci', ['entity_type'])
      ->condition('fc.active', 1)
      ->condition('fc.storage_active', 1)
      ->condition('fc.deleted', 0)
      ->condition('fci.deleted', 0);
    $query->join('field_config_instance', 'fci', '[fc].[id] = [fci].[field_id]');

    // The Title module fields are not migrated.
    if ($this->moduleExists('title')) {
      $title_fields = [
        'title_field',
        'name_field',
        'description_field',
        'subject_field',
      ];
      $query->condition('fc.field_name', $title_fields, 'NOT IN');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'id' => $this->t('The field ID.'),
      'field_name' => $this->t('The field name.'),
      'type' => $this->t('The field type.'),
      'module' => $this->t('The module that implements the field type.'),
      'active' => $this->t('The field status.'),
      'storage_type' => $this->t('The field storage type.'),
      'storage_module' => $this->t('The module that implements the field storage type.'),
      'storage_active' => $this->t('The field storage status.'),
      'locked' => $this->t('Locked'),
      'data' => $this->t('The field data.'),
      'cardinality' => $this->t('Cardinality'),
      'translatable' => $this->t('Translatable'),
      'deleted' => $this->t('Deleted'),
      'instances' => $this->t('The field instances.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row, $keep = TRUE) {
    foreach (unserialize($row->getSourceProperty('data')) as $key => $value) {
      $row->setSourceProperty($key, $value);
    }

    $instances = $this->select('field_config_instance', 'fci')
      ->fields('fci')
      ->condition('field_name', $row->getSourceProperty('field_name'))
      ->condition('entity_type', $row->getSourceProperty('entity_type'))
      ->execute()
      ->fetchAll();
    $row->setSourceProperty('instances', $instances);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'field_name' => [
        'type' => 'string',
        'alias' => 'fc',
      ],
      'entity_type' => [
        'type' => 'string',
        'alias' => 'fci',
      ],
    ];
  }

}
