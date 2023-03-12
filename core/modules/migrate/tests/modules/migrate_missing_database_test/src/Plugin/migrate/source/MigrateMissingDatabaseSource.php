<?php

namespace Drupal\migrate_missing_database_test\Plugin\migrate\source;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * A simple migrate source for the missing database tests.
 *
 * @MigrateSource(
 *   id = "migrate_missing_database_test",
 *   source_module = "migrate_missing_database_test",
 *   requirements_met = true
 * )
 */
class MigrateMissingDatabaseSource extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query(): SelectInterface {
    $field_names = ['id'];
    $query = $this
      ->select('missing_database', 'm')
      ->fields('m', $field_names);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $fields = [
      'id' => $this->t('ID'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds(): array {
    return [
      'id' => [
        'type' => 'integer',
      ],
    ];
  }

}
