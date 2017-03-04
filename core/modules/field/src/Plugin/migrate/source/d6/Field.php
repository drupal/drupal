<?php

namespace Drupal\field\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 field source from database.
 *
 * @MigrateSource(
 *   id = "d6_field",
 *   source_provider = "content"
 * )
 */
class Field extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('content_node_field', 'cnf')
      ->fields('cnf', [
        'field_name',
        'type',
        'global_settings',
        'required',
        'multiple',
        'db_storage',
        'module',
        'db_columns',
        'active',
        'locked',
      ])
      ->distinct();
    // Only import fields which are actually being used.
    $query->innerJoin('content_node_field_instance', 'cnfi', 'cnfi.field_name = cnf.field_name');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'field_name' => $this->t('Field name'),
      'type' => $this->t('Type (text, integer, ....)'),
      'widget_type' => $this->t('An instance-specific widget type'),
      'global_settings' => $this->t('Global settings. Shared with every field instance.'),
      'required' => $this->t('Required'),
      'multiple' => $this->t('Multiple'),
      'db_storage' => $this->t('DB storage'),
      'module' => $this->t('Module'),
      'db_columns' => $this->t('DB Columns'),
      'active' => $this->t('Active'),
      'locked' => $this->t('Locked'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // The instance widget_type helps determine what D8 field type we'll use.
    // Identify the distinct widget_types being used in D6.
    $widget_types = $this->select('content_node_field_instance', 'cnfi')
      ->fields('cnfi', ['widget_type'])
      ->condition('field_name', $row->getSourceProperty('field_name'))
      ->distinct()
      ->orderBy('widget_type')
      ->execute()
      ->fetchCol();
    // Arbitrarily use the first widget_type - if there are multiples, let the
    // migrator know.
    $row->setSourceProperty('widget_type', $widget_types[0]);
    if (count($widget_types) > 1) {
      $this->migration->getIdMap()->saveMessage(
        ['field_name' => $row->getSourceProperty('field_name')],
        $this->t('Widget types @types are used in Drupal 6 field instances: widget type @selected_type applied to the Drupal 8 base field', [
          '@types' => implode(', ', $widget_types),
          '@selected_type' => $widget_types[0],
        ])
      );
    }

    // Unserialize data.
    $global_settings = unserialize($row->getSourceProperty('global_settings'));
    $db_columns = unserialize($row->getSourceProperty('db_columns'));
    $row->setSourceProperty('global_settings', $global_settings);
    $row->setSourceProperty('db_columns', $db_columns);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['field_name'] = [
      'type' => 'string',
      'alias' => 'cnf',
    ];
    return $ids;
  }

}
