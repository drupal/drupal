<?php

namespace Drupal\field\Plugin\migrate\source\d7;

/**
 * Drupal 7 field instance per view mode source class.
 *
 * For available configuration keys, refer to the parent classes:
 * @see \Drupal\field\Plugin\migrate\source\d7\FieldInstance
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "d7_field_instance_per_view_mode",
 *   source_module = "field"
 * )
 */
class FieldInstancePerViewMode extends FieldInstance {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $instances = parent::initializeIterator();

    $rows = [];
    foreach ($instances->getArrayCopy() as $instance) {
      $data = unserialize($instance['data']);
      foreach ($data['display'] as $view_mode => $formatter) {
        $rows[] = array_merge($instance, [
          'view_mode' => $view_mode,
          'formatter' => $formatter,
        ]);
      }
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return array_merge(parent::fields(), [
      'view_mode' => $this->t('The original machine name of the view mode.'),
      'formatter' => $this->t('The formatter settings.'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type' => [
        'type' => 'string',
      ],
      'bundle' => [
        'type' => 'string',
      ],
      'view_mode' => [
        'type' => 'string',
      ],
      'field_name' => [
        'type' => 'string',
      ],
    ];
  }

}
