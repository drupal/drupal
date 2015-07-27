<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\pgsql\Condition.
 */

namespace Drupal\Core\Entity\Query\Sql\pgsql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\Sql\Condition as BaseCondition;

/**
 * Implements entity query conditions for PostgreSQL databases.
 */
class Condition extends BaseCondition {

  /**
   * {@inheritdoc}
   */
  public static function translateCondition(&$condition, SelectInterface $sql_query, $case_sensitive) {
    if (is_array($condition['value']) && $case_sensitive === FALSE) {
      $condition['where'] = 'LOWER(' . $sql_query->escapeField($condition['real_field']) . ') ' . $condition['operator'] . ' (';
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
      return;
    }
    parent::translateCondition($condition, $sql_query, $case_sensitive);
  }
}
