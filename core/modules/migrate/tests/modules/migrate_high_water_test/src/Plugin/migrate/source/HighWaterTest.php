<?php

declare(strict_types=1);

namespace Drupal\migrate_high_water_test\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for migration high water tests.
 */
#[MigrateSource('high_water_test')]
class HighWaterTest extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field_names = array_keys($this->fields());
    $query = $this
      ->select('high_water_node', 'm')
      ->fields('m', $field_names);
    foreach ($field_names as $field_name) {
      $query->groupBy($field_name);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => 'Id',
      'title' => 'Title',
      'changed' => 'Changed',
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

}
