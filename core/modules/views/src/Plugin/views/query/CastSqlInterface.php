<?php

namespace Drupal\views\Plugin\views\query;

/**
 * Defines an interface for defining cast expressions in SQL.
 */
interface CastSqlInterface {

  /**
   * Returns a database expression to cast the field to int.
   *
   * @param string $field
   *   The database field to cast.
   *
   * @return string
   *   The database expression to cast the field to int.
   */
  public function getFieldAsInt(string $field): string;

}
