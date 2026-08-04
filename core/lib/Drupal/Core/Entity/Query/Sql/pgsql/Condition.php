<?php

namespace Drupal\Core\Entity\Query\Sql\pgsql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\Sql\Condition as BaseCondition;

@trigger_error('\Drupal\Core\Entity\Query\Sql\pgsql\Condition is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. The PostgreSQL override of the entity query has been moved to the pgsql module. See https://www.drupal.org/node/3488580', E_USER_DEPRECATED);

/**
 * Implements entity query conditions for PostgreSQL databases.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. The
 *   PostgreSQL override of the entity query has been moved to the pgsql module.
 *
 * @see https://www.drupal.org/node/3488580
 */
class Condition extends BaseCondition {

  /**
   * {@inheritdoc}
   */
  public static function translateCondition(&$condition, SelectInterface $sql_query, $case_sensitive) {
    if (is_array($condition['value']) && $case_sensitive === FALSE) {
      if (!in_array($condition['operator'], ['IN', 'NOT IN'], TRUE)) {
        throw new \InvalidArgumentException(sprintf('Invalid operator "%s" for a case-insensitive array condition. Allowed operators are "IN" and "NOT IN".', $condition['operator']));
      }
      $condition['where'] = 'LOWER(' . $sql_query->escapeField($condition['real_field']) . ') ' . $condition['operator'] . ' (';
      $condition['where_args'] = [];

      // Only use the array values in case an associative array is passed as an
      // argument following similar pattern in
      // \Drupal\Core\Database\Connection::expandArguments().
      $where_prefix = str_replace('.', '_', $condition['real_field']);
      foreach (array_values($condition['value']) as $key => $value) {
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
