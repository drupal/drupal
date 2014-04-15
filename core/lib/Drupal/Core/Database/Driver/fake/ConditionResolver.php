<?php

/**
 * @file
 * Contains Drupal\Core\Database\Driver\fake\ConditionResolver.
 */


namespace Drupal\Core\Database\Driver\fake;

use Drupal\Core\Database\Query\Condition;

class ConditionResolver {

  /**
   * Match a row against a group of conditions.
   *
   * @param \Drupal\Core\Database\Driver\fake\DatabaseRowInterface $row
   *   The database row object.
   * @param \Drupal\Core\Database\Query\Condition $condition_group
   *   The condition group object.
   *
   * @return bool
   *   TRUE if there is a match.
   */
  public static function matchGroup(DatabaseRowInterface $row, Condition $condition_group) {
    $conditions = $condition_group->conditions();
    $and = $conditions['#conjunction'] == 'AND';
    unset($conditions['#conjunction']);
    $match = TRUE;
    foreach ($conditions as $condition) {
      $match = $condition['field'] instanceof Condition ? static::matchGroup($row, $condition['field']) : static::matchSingle($row, $condition);
      // For AND, finish matching on the first fail. For OR, finish on first
      // success.
      if ($and != $match) {
        break;
      }
    }
    return $match;
  }

  /**
   * Match a single row and its condition.
   *
   * @param \Drupal\migrate\tests\DatabaseRowInterface $row
   *   The row to match.
   *
   * @param array $condition
   *   An array representing a single condition.
   *
   * @return bool
   *   TRUE if the condition matches.
   *
   * @throws \Exception
   */
  protected static function matchSingle(DatabaseRowInterface $row, array $condition) {
    $row_value = $row->getValue($condition['field']);
    switch ($condition['operator']) {
      case '=':
        return $row_value == $condition['value'];

      case '<=':
        return $row_value <= $condition['value'];

      case '>=':
        return $row_value >= $condition['value'];

      case '!=':
        return $row_value != $condition['value'];

      case '<>':
        return $row_value != $condition['value'];

      case '<':
        return $row_value < $condition['value'];

      case '>':
        return $row_value > $condition['value'];

      case 'IN':
        return in_array($row_value, $condition['value']);

      case 'IS NULL':
        return !isset($row_value);

      case 'IS NOT NULL':
        return isset($row_value);

      default:
        throw new \Exception(sprintf('operator %s is not supported', $condition['operator']));
    }
  }

}
