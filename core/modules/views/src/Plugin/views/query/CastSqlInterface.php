<?php

namespace Drupal\views\Plugin\views\query;

/**
 * Defines an interface for defining cast expressions in SQL.
 */
interface CastSqlInterface {

  /**
   * Returns a database expression to cast the field to int.
   *
   * @param $field string
   *   The database field to cast.
   *
   * @return string
   */
  public function getFieldAsInt(string $field): string;

}
