<?php

declare(strict_types=1);

namespace Drupal\migrate_track_changes_test\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for migration track changes tests.
 */
#[MigrateSource('track_changes_test')]
class TrackChangesTest extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field_names = array_keys($this->fields());
    $query = $this
      ->select('track_changes_term', 't')
      ->fields('t', $field_names);
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
      'tid' => 'Term id',
      'name' => 'Name',
      'description' => 'Description',
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'tid' => [
        'type' => 'integer',
      ],
    ];
  }

}
