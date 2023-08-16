<?php

namespace Drupal\mysql\Plugin\views\query;

use Drupal\views\Plugin\views\query\CastSqlInterface;

/**
 * MySQL specific cast handling.
 */
class MysqlCastSql implements CastSqlInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldAsInt(string $field): string {
    return "CAST($field AS UNSIGNED)";
  }

}
