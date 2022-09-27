<?php

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\ConditionBase;
use Drupal\Core\Entity\Query\ConditionInterface;

/**
 * Implements entity query conditions for SQL databases.
 */
class Condition extends ConditionBase {

  /**
   * Whether this condition is nested inside an OR condition.
   *
   * @var bool
   */
  protected $nestedInsideOrCondition = FALSE;

  /**
   * The SQL entity query object this condition belongs to.
   *
   * @var \Drupal\Core\Entity\Query\Sql\Query
   */
  protected $query;

  /**
   * The current SQL query, set by parent condition compile() method calls.
   *
   * @var \Drupal\Core\Database\Query\SelectInterface
   */
  protected $sqlQuery;

  /**
   * {@inheritdoc}
   */
  public function compile($conditionContainer) {

    // If this is not the top level condition group then the sql query is
    // added to the $conditionContainer object by this function itself. The
    // SQL query object is only necessary to pass to Query::addField() so it
    // can join tables as necessary. On the other hand, conditions need to be
    // added to the $conditionContainer object to keep grouping.
    $sql_query = $conditionContainer instanceof SelectInterface ? $conditionContainer : $this->sqlQuery;
    $tables = $this->query->getTables($sql_query);
    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceof ConditionInterface) {
        $sql_condition = $sql_query->getConnection()->condition($condition['field']->getConjunction());
        // Add the SQL query to the object before calling this method again.
        $condition['field']->sqlQuery = $sql_query;
        $condition['field']->nestedInsideOrCondition = $this->nestedInsideOrCondition || strtoupper($this->conjunction) === 'OR';
        $condition['field']->compile($sql_condition);
        $conditionContainer->condition($sql_condition);
      }
      else {
        $type = $this->nestedInsideOrCondition || strtoupper($this->conjunction) === 'OR' || $condition['operator'] === 'IS NULL' ? 'LEFT' : 'INNER';
        $field = $tables->addField($condition['field'], $type, $condition['langcode']);
        // If the field is trying to query on %delta for a single value field
        // then the only supported delta is 0. No other value than 0 makes
        // sense. \Drupal\Core\Entity\Query\Sql\Tables::addField() returns 0 as
        // the field name for single value fields when querying on their %delta.
        if ($field === 0) {
          if ($condition['value'] != 0) {
            $conditionContainer->alwaysFalse();
          }
          continue;
        }
        $condition['real_field'] = $field;
        static::translateCondition($condition, $sql_query, $tables->isFieldCaseSensitive($condition['field']));

        // Add the translated conditions back to the condition container.
        if (isset($condition['where']) && isset($condition['where_args'])) {
          $conditionContainer->where($condition['where'], $condition['where_args']);
        }
        else {
          $conditionContainer->condition($field, $condition['value'], $condition['operator']);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $langcode = NULL) {
    return $this->condition($field, NULL, 'IS NULL', $langcode);
  }

  /**
   * Translates the string operators to SQL equivalents.
   *
   * @param array $condition
   *   The condition array.
   * @param \Drupal\Core\Database\Query\SelectInterface $sql_query
   *   Select query instance.
   * @param bool|null $case_sensitive
   *   If the condition should be case sensitive or not, NULL if the field does
   *   not define it.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::condition()
   */
  public static function translateCondition(&$condition, SelectInterface $sql_query, $case_sensitive) {
    // // There is nothing we can do for IN ().
    if (is_array($condition['value'])) {
      return;
    }

    // Ensure that the default operator is set to simplify the cases below.
    if (empty($condition['operator'])) {
      $condition['operator'] = '=';
    }
    switch ($condition['operator']) {
      case '=':
        // If a field explicitly requests that queries should not be case
        // sensitive, use the LIKE operator, otherwise keep =.
        if ($case_sensitive === FALSE) {
          $condition['value'] = $sql_query->escapeLike($condition['value']);
          $condition['operator'] = 'LIKE';
        }
        break;

      case '<>':
        // If a field explicitly requests that queries should not be case
        // sensitive, use the NOT LIKE operator, otherwise keep <>.
        if ($case_sensitive === FALSE) {
          $condition['value'] = $sql_query->escapeLike($condition['value']);
          $condition['operator'] = 'NOT LIKE';
        }
        break;

      case 'STARTS_WITH':
        if ($case_sensitive) {
          $condition['operator'] = 'LIKE BINARY';
        }
        else {
          $condition['operator'] = 'LIKE';
        }
        $condition['value'] = $sql_query->escapeLike($condition['value']) . '%';
        break;

      case 'CONTAINS':
        if ($case_sensitive) {
          $condition['operator'] = 'LIKE BINARY';
        }
        else {
          $condition['operator'] = 'LIKE';
        }
        $condition['value'] = '%' . $sql_query->escapeLike($condition['value']) . '%';
        break;

      case 'ENDS_WITH':
        if ($case_sensitive) {
          $condition['operator'] = 'LIKE BINARY';
        }
        else {
          $condition['operator'] = 'LIKE';
        }
        $condition['value'] = '%' . $sql_query->escapeLike($condition['value']);
        break;
    }
  }

}
