<?php

/**
 * @file
 * Contains \Drupal\Core\Database\EntityQuery\ConditionInterface.
 */

namespace Drupal\Core\Database\EntityQuery;

/**
 * Provides an interface for entity query conditions for SQL databases.
 */
interface ConditionInterface {

  /**
   * Translates the string operators to SQL equivalents. This is a no-op method
   * for MySQL and SQLite databases but allows override if needed, e.g. for
   * PostgreSQL to support case insensitive queries.
   *
   * @param array $condition
   *   The condition array.
   * @param bool|null $case_sensitive
   *   If the condition should be case sensitive or not, NULL if the field does
   *   not define it.
   *
   * @see \Drupal\Core\Entity\Query\Sql\Condition::$entityQueryCondition
   */
  public static function translateCondition(array &$condition, $case_sensitive);

}
