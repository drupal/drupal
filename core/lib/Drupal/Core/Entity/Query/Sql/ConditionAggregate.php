<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\ConditionAggregate.
 */

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\ConditionAggregateBase;
use Drupal\Core\Entity\Query\ConditionAggregateInterface;

/**
 * Defines the aggregate condition for sql based storage.
 */
class ConditionAggregate extends ConditionAggregateBase {

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::compile().
   */
  public function compile($conditionContainer) {
    // If this is not the top level condition group then the sql query is
    // added to the $conditionContainer object by this function itself. The
    // SQL query object is only necessary to pass to Query::addField() so it
    // can join tables as necessary. On the other hand, conditions need to be
    // added to the $conditionContainer object to keep grouping.
    $sql_query = ($conditionContainer instanceof SelectInterface) ? $conditionContainer : $conditionContainer->sqlQuery;
    $tables = new Tables($sql_query);
    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceOf ConditionAggregateInterface) {
        $sql_condition = new Condition($condition['field']->getConjunction());
        // Add the SQL query to the object before calling this method again.
        $sql_condition->sqlQuery = $sql_query;
        $condition['field']->compile($sql_condition);
        $sql_query->condition($sql_condition);
      }
      else {
        $type = ((strtoupper($this->conjunction) == 'OR') || ($condition['operator'] == 'IS NULL')) ? 'LEFT' : 'INNER';
        $this->translateCondition($condition);
        $field = $tables->addField($condition['field'], $type, $condition['langcode']);
        $function = $condition['function'];
        $placeholder = ':db_placeholder_' . $conditionContainer->nextPlaceholder();
        $conditionContainer->having("$function($field) {$condition['operator']} $placeholder", array($placeholder => $condition['value']));
      }
    }
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::exists().
   */
  public function exists($field, $function, $langcode = NULL) {
    return $this->condition($field, $function, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * Implements \Drupal\Core\Entity\Query\ConditionInterface::notExists().
   */
  public function notExists($field, $function, $langcode = NULL) {
    return $this->condition($field, $function, NULL, 'IS NULL', $langcode);
  }

  /**
   * Translates the string operators to SQL equivalents.
   *
   * @param array $condition
   *   An associative array containing the following keys:
   *     - value: The value to filter by
   *     - operator: The operator to use for comparison, for example "=".
   */
  protected function translateCondition(&$condition) {
    switch ($condition['operator']) {
      case 'STARTS_WITH':
        $condition['value'] .= '%';
        $condition['operator'] = 'LIKE';
        break;
      case 'CONTAINS':
        $condition['value'] = '%' . $condition['value'] . '%';
        $condition['operator'] = 'LIKE';
        break;
      case 'ENDS_WITH':
        $condition['value'] = '%' . $condition['value'];
        $condition['operator'] = 'LIKE';
        break;
    }
  }

}
