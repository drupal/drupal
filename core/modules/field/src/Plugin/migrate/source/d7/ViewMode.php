<?php

/**
 * @file
 * Contains \Drupal\field\Plugin\migrate\source\d7\ViewMode.
 */

namespace Drupal\field\Plugin\migrate\source\d7;

use Drupal\migrate\Annotation\MigrateSource;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * @MigrateSource(
 *  id = "d7_view_mode"
 * )
 */
class ViewMode extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $rows = [];
    $result = $this->prepareQuery()->execute();
    foreach ($result as $field_instance) {
      $data = unserialize($field_instance['data']);
      foreach (array_keys($data['display']) as $view_mode) {
        $key = $field_instance['entity_type'] . '.' . $view_mode;
        $rows[$key] = [
          'entity_type' => $field_instance['entity_type'],
          'view_mode' => $view_mode,
        ];
      }
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'view_mode' => $this->t('The view mode ID.'),
      'entity_type' => $this->t('The entity type ID.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('field_config_instance', 'fci')
      ->fields('fci', ['entity_type', 'data']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
      ],
      'view_mode' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->initializeIterator()->count();
  }

}
