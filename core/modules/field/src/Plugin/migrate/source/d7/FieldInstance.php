<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\source\d7\FieldInstance.
 */

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 field instances source from database.
 *
 * @MigrateSource(
 *   id = "d7_field_instance",
 * )
 */
class FieldInstance extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('field_config_instance', 'fci')
      ->fields('fci')
      ->condition('fci.deleted', 0)
      ->condition('fc.active', 1)
      ->condition('fc.deleted', 0)
      ->condition('fc.storage_active', 1);
    $query->innerJoin('field_config', 'fc', 'fci.field_id = fc.id');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'field_name' => $this->t('The machine name of field.'),
      'entity_type' => $this->t('The entity type.'),
      'bundle' => $this->t('The entity bundle.'),
      'default_value' => $this->t('Default value'),
      'instance_settings' => $this->t('Field instance settings.'),
      'widget_settings' => $this->t('Widget settings.'),
      'display_settings' => $this->t('Display settings.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $data = unserialize($row->getSourceProperty('data'));

    $row->setSourceProperty('label', $data['label']);
    $row->setSourceProperty('description', $data['description']);
    $row->setSourceProperty('required', $data['required']);

    $default_value = !empty($data['default_value']) ? $data['default_value'] : array();
    if ($data['widget']['type'] == 'email_textfield' && $default_value) {
      $default_value[0]['value'] = $default_value[0]['email'];
      unset($default_value[0]['email']);
    }
    $row->setSourceProperty('default_value', $default_value);

    // Settings.
    $row->setSourceProperty('instance_settings', $data['settings']);
    $row->setSourceProperty('widget_settings', $data['widget']);
    $row->setSourceProperty('display_settings', $data['display']);

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'entity_type' => array(
        'type' => 'string',
        'alias' => 'fci',
      ),
      'bundle' => array(
        'type' => 'string',
        'alias' => 'fci',
      ),
      'field_name' => array(
        'type' => 'string',
        'alias' => 'fci',
      ),
    );
  }
}
