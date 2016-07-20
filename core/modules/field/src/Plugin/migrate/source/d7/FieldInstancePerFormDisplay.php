<?php

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * The field instance per form display source class.
 *
 * @MigrateSource(
 *   id = "d7_field_instance_per_form_display"
 * )
 */
class FieldInstancePerFormDisplay extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config_instance', 'fci')
      ->fields('fci', array(
        'field_name',
        'bundle',
        'data',
        'entity_type'
      ))
      ->fields('fc', array(
        'type',
        'module',
      ));
    $query->join('field_config', 'fc', 'fci.field_id = fc.id');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $data = unserialize($row->getSourceProperty('data'));
    $row->setSourceProperty('widget', $data['widget']);
    $row->setSourceProperty('widget_settings', $data['widget']['settings']);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'field_name' => $this->t('The machine name of field.'),
      'bundle' => $this->t('Content type where this field is used.'),
      'data' => $this->t('Field configuration data.'),
      'entity_type' => $this->t('The entity type.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'bundle' => array(
        'type' => 'string',
      ),
      'field_name' => array(
        'type' => 'string',
        'alias' => 'fci',
      ),
    );
  }

}
