<?php

namespace Drupal\views\Plugin\views\query;

/**
 * Cast handling in SQL.
 */
class CastSql implements CastSqlInterface {

  /**
   * {@inheritdoc}
   */
  public function getFieldAsInt(string $field): string {
    return "CAST($field AS INTEGER)";
  }

}
