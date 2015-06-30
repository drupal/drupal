<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\pgsql\EntityQuery\Condition
 */

namespace Drupal\Core\Database\Driver\pgsql\EntityQuery;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\EntityQuery\ConditionInterface as EntityQueryConditionInterface;

/**
 * Implements entity query conditions for SQL databases.
 */
class Condition implements EntityQueryConditionInterface {

  /**
   * {@inheritdoc}
   */
  public static function translateCondition(array &$condition, $case_sensitive) {
    $connection = Database::getConnection();

    // For PostgreSQL all the condition arguments need to have case
    // lowered to support not case sensitive fields.
    if (is_array($condition['value']) && $case_sensitive === FALSE) {
      $condition['where'] = 'LOWER(' . $connection->escapeField($condition['real_field']) . ') ' . $condition['operator'] . ' (';
      $condition['where_args'] = [];

      $n = 1;
      // Only use the array values in case an associative array is passed as an
      // argument following similar pattern in
      // \Drupal\Core\Database\Connection::expandArguments().
      foreach ($condition['value'] as $value) {
        $condition['where'] .= 'LOWER(:value' . $n . '),';
        $condition['where_args'][':value' . $n] = $value;
        $n++;
      }
      $condition['where'] = trim($condition['where'], ',');
      $condition['where'] .= ')';
    }
  }

}
