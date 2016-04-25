<?php

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * The field instance per view mode source class.
 *
 * @MigrateSource(
 *   id = "d7_field_instance_per_view_mode",
 *   source_provider = "field"
 * )
 */
class FieldInstancePerViewMode extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $rows = array();
    $result = $this->prepareQuery()->execute();
    foreach ($result as $field_instance) {
      $data = unserialize($field_instance['data']);
      // We don't need to include the serialized data in the returned rows.
      unset($field_instance['data']);
      foreach ($data['display'] as $view_mode => $info) {
        $rows[] = array_merge($field_instance, $info, array('view_mode' => $view_mode));
      }
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('field_config_instance', 'fci')
      ->fields('fci', array('entity_type', 'bundle', 'field_name', 'data'));
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array(
      'entity_type' => $this->t('The entity type ID.'),
      'bundle' => $this->t('The bundle ID.'),
      'field_name' => $this->t('Machine name of the field.'),
      'view_mode' => $this->t('The original machine name of the view mode.'),
      'label' => $this->t('The display label of the field.'),
      'type' => $this->t('The formatter ID.'),
      'settings' => $this->t('Array of formatter-specific settings.'),
      'module' => $this->t('The module providing the formatter.'),
      'weight' => $this->t('Display weight of the field.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return array(
      'entity_type' => array(
        'type' => 'string',
      ),
      'bundle' => array(
        'type' => 'string',
      ),
      'view_mode' => array(
        'type' => 'string',
      ),
      'field_name' => array(
        'type' => 'string',
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->initializeIterator()->count();
  }

}
