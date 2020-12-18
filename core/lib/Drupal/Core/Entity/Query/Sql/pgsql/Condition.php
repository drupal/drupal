<?php

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

      // Only use the array values in case an associative array is passed as an
      // argument following similar pattern in
      // \Drupal\Core\Database\Connection::expandArguments().
      $where_prefix = str_replace('.', '_', $condition['real_field']);
      foreach ($condition['value'] as $key => $value) {
        $where_id = $where_prefix . $key;
        $condition['where'] .= 'LOWER(:' . $where_id . '),';
        $condition['where_args'][':' . $where_id] = $value;
      }
      $condition['where'] = trim($condition['where'], ',');
      $condition['where'] .= ')';
    }
    parent::translateCondition($condition, $sql_query, $case_sensitive);
  }

}
