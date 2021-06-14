<?php

namespace Drupal\field\Plugin\migrate\source\d7;

/**
 * The view mode source class.
 *
 * @MigrateSource(
 *   id = "d7_view_mode",
 *   source_module = "field"
 * )
 */
class ViewMode extends FieldInstance {

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $instances = parent::initializeIterator();

    $rows = [];
    foreach ($instances->getArrayCopy() as $instance) {
      $data = unserialize($instance['data']);
      foreach (array_keys($data['display']) as $view_mode) {
        $key = $instance['entity_type'] . '.' . $view_mode;
        $rows[$key] = array_merge($instance, [
          'view_mode' => $view_mode,
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
      'view_mode' => $this->t('The view mode ID.'),
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
      'view_mode' => [
        'type' => 'string',
      ],
    ];
  }

}
