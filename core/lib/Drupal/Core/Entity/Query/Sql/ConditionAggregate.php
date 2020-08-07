<?php

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\Query\ConditionAggregateBase;
use Drupal\Core\Entity\Query\ConditionAggregateInterface;
use Drupal\Core\Entity\Query\QueryBase;

/**
 * Defines the aggregate condition for sql based storage.
 */
class ConditionAggregate extends ConditionAggregateBase {

  /**
   * {@inheritdoc}
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
      if ($condition['field'] instanceof ConditionAggregateInterface) {
        $sql_condition = $sql_query->getConnection()->condition($condition['field']->getConjunction());
        // Add the SQL query to the object before calling this method again.
        $sql_condition->sqlQuery = $sql_query;
        $condition['field']->compile($sql_condition);
        $sql_query->condition($sql_condition);
      }
      else {
        $type = ((strtoupper($this->conjunction) == 'OR') || ($condition['operator'] == 'IS NULL')) ? 'LEFT' : 'INNER';
        $field = $tables->addField($condition['field'], $type, $condition['langcode']);
        $condition_class = QueryBase::getClass($this->namespaces, 'Condition');
        $condition_class::translateCondition($condition, $sql_query, $tables->isFieldCaseSensitive($condition['field']));
        $function = $condition['function'];
        $placeholder = ':db_placeholder_' . $conditionContainer->nextPlaceholder();
        $conditionContainer->having("$function($field) {$condition['operator']} $placeholder", [$placeholder => $condition['value']]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($field, $function, $langcode = NULL) {
    return $this->condition($field, $function, NULL, 'IS NOT NULL', $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExists($field, $function, $langcode = NULL) {
    return $this->condition($field, $function, NULL, 'IS NULL', $langcode);
  }

}
