<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\source\d6\FieldInstance.
 */

namespace Drupal\field\Plugin\migrate\source\d6;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 field instances source from database.
 *
 * @MigrateSource(
 *   id = "d6_field_instance",
 *   source_provider = "content"
 * )
 */
class FieldInstance extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('content_node_field_instance', 'cnfi')->fields('cnfi');
    if (isset($this->configuration['node_type'])) {
      $query->condition('cnfi.type_name', $this->configuration['node_type']);
    }
    $query->join('content_node_field', 'cnf', 'cnf.field_name = cnfi.field_name');
    $query->fields('cnf');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'field_name' => $this->t('The machine name of field.'),
      'type_name' => $this->t('Content type where this field is in use.'),
      'weight' => $this->t('Weight.'),
      'label' => $this->t('A name to show.'),
      'widget_type' => $this->t('Widget type.'),
      'widget_settings' => $this->t('Serialize data with widget settings.'),
      'display_settings' => $this->t('Serialize data with display settings.'),
      'description' => $this->t('A description of field.'),
      'widget_module' => $this->t('Module that implements widget.'),
      'widget_active' => $this->t('Status of widget'),
      'module' => $this->t('The module that provides the field.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Unserialize data.
    $widget_settings = unserialize($row->getSourceProperty('widget_settings'));
    $display_settings = unserialize($row->getSourceProperty('display_settings'));
    $global_settings = unserialize($row->getSourceProperty('global_settings'));
    $row->setSourceProperty('widget_settings', $widget_settings);
    $row->setSourceProperty('display_settings', $display_settings);
    $row->setSourceProperty('global_settings', $global_settings);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = array(
      'field_name' => array(
        'type' => 'string',
        'alias' => 'cnfi',
      ),
      'type_name' => array(
        'type' => 'string',
      ),
    );
    return $ids;
  }

}
