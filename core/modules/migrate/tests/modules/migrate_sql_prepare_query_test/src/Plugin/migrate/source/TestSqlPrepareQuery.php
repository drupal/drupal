<?php

declare(strict_types=1);

namespace Drupal\migrate_sql_prepare_query_test\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Source plugin for prepare query test.
 */
#[MigrateSource('test_sql_prepare_query')]
class TestSqlPrepareQuery extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('migrate_source_test')->fields('migrate_source_test');
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareQuery() {
    $this->query = parent::prepareQuery();
    $this->query->condition('name', 'foo', '!=');
    return $this->query;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['id' => ['type' => 'integer']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return ['id' => 'ID', 'name' => 'Name'];
  }

}
