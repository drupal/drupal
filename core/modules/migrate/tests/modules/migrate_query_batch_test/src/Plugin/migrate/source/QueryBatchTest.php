<?php

declare(strict_types=1);

namespace Drupal\migrate_query_batch_test\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for migration high water tests.
 *
 * @MigrateSource(
 *   id = "query_batch_test"
 * )
 */
class QueryBatchTest extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return ($this->select('query_batch_test', 'q')->fields('q'));
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'id' => 'Id',
      'data' => 'data',
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
