<?php

/**
 * @file
 * Contains \Drupal\Core\Entity\Query\Sql\Condition.
 */

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Entity\Query\ConditionBase;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\Query\Condition as SqlCondition;

/**
 * Implements entity query conditions for SQL databases.
 */
class Condition extends ConditionBase {

  /**
   * The SQL entity query object this condition belongs to.
   *
   * @var \Drupal\Core\Entity\Query\Sql\Query
   */
  protected $query;

  /**
   * {@inheritdoc}
   */
  public function compile($conditionContainer) {
    // If this is not the top level condition group then the sql query is
    // added to the $conditionContainer object by this function itself. The
    // SQL query object is only necessary to pass to Query::addField() so it
    // can join tables as necessary. On the other hand, conditions need to be
    // added to the $conditionContainer object to keep grouping.
    $sqlQuery = $conditionContainer instanceof SelectInterface ? $conditionContainer : $conditionContainer->sqlQuery;
    $tables = $this->query->getTables($sqlQuery);
    foreach ($this->conditions as $condition) {
      if ($condition['field'] instanceOf ConditionInterface) {
        $sqlCondition = new SqlCondition($condition['field']->getConjunction());
        // Add the SQL query to the object before calling this method again.
        $sqlCondition->sqlQuery = $sqlQuery;
        $condition['field']->compile($sqlCondition);
        $sqlQuery->condition($sqlCondition);
      }
      else {
        $type = strtoupper($this->conjunction) == 'OR' || $condition['operator'] == 'IS NULL' ? 'LEFT' : 'INNER';
        $this->translateCondition($condition);
        $field = $tables->addField($condition['field'], $type, $condition['langcode']);
        $conditionContainer->condition($field, $condition['value'], $condition['operator']);
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
