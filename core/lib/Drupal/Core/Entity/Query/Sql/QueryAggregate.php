<?php

namespace Drupal\Core\Entity\Query\Sql;

use Drupal\Core\Entity\Query\QueryAggregateInterface;

/**
 * The SQL storage entity query aggregate class.
 */
class QueryAggregate extends Query implements QueryAggregateInterface {

  /**
   * Stores the sql expressions used to build the sql query.
   *
   * @var array
   *   An array of expressions.
   */
  protected $sqlExpressions = [];

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this
      ->prepare()
      ->addAggregate()
      ->compile()
      ->compileAggregate()
      ->addGroupBy()
      ->addSort()
      ->addSortAggregate()
      ->finish()
      ->result();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare() {
    parent::prepare();
    // Throw away the id fields.
    $this->sqlFields = [];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function conditionAggregateGroupFactory($conjunction = 'AND') {
    $class = static::getClass($this->namespaces, 'ConditionAggregate');
    return new $class($conjunction, $this, $this->namespaces);
  }

  /**
   * {@inheritdoc}
   */
  public function existsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->exists($field, $function, $langcode);
  }

  /**
   * {@inheritdoc}
   */
  public function notExistsAggregate($field, $function, $langcode = NULL) {
    return $this->conditionAggregate->notExists($field, $function, $langcode);
  }

  /**
   * Adds the aggregations to the query.
   *
   * @return $this
   *   Returns the called object.
   */
  protected function addAggregate() {
    if ($this->aggregate) {
      foreach ($this->aggregate as $aggregate) {
        $sql_field = $this->getSqlField($aggregate['field'], $aggregate['langcode']);
        $this->sqlExpressions[$aggregate['alias']] = $aggregate['function'] . "($sql_field)";
      }
    }
    return $this;
  }

  /**
   * Builds the aggregation conditions part of the query.
   *
   * @return $this
   *   Returns the called object.
   */
  protected function compileAggregate() {
    $this->conditionAggregate->compile($this->sqlQuery);
    return $this;
  }

  /**
   * Adds the groupby values to the actual query.
   *
   * @return $this
   *   Returns the called object.
   */
  protected function addGroupBy() {
    foreach ($this->groupBy as $group_by) {
      $field = $group_by['field'];
      $sql_field = $this->getSqlField($field, $group_by['langcode']);
      $this->sqlGroupBy[$sql_field] = $sql_field;
      list($table, $real_sql_field) = explode('.', $sql_field);
      $this->sqlFields[$sql_field] = [$table, $real_sql_field, $this->createSqlAlias($field, $real_sql_field)];
    }

    return $this;
  }

  /**
   * Builds the aggregation sort part of the query.
   *
   * @return $this
   *   Returns the called object.
   */
  protected function addSortAggregate() {
    if (!$this->count) {
      foreach ($this->sortAggregate as $alias => $sort) {
        $this->sqlQuery->orderBy($alias, $sort['direction']);
      }
    }
    return $this;
  }

  /**
   * Overrides \Drupal\Core\Entity\Query\Sql\Query::finish().
   *
   * Adds the sql expressions to the query.
   */
  protected function finish() {
    foreach ($this->sqlExpressions as $alias => $expression) {
      $this->sqlQuery->addExpression($expression, $alias);
    }
    return parent::finish();
  }

  /**
   * Builds a sql alias as expected in the result.
   *
   * @param string $field
   *   The field as passed in by the caller.
   * @param string $sql_field
   *   The sql field as returned by getSqlField.
   *
   * @return string
   *   The SQL alias expected in the return value. The dots in $sql_field are
   *   replaced with underscores and if a default fallback to .value happened,
   *   the _value is stripped.
   */
  public function createSqlAlias($field, $sql_field) {
    $alias = str_replace('.', '_', $sql_field);
    // If the alias contains of field_*_value remove the _value at the end.
    if (substr($alias, 0, 6) === 'field_' && substr($field, -6) !== '_value' && substr($alias, -6) === '_value') {
      $alias = substr($alias, 0, -6);
    }
    return $alias;
  }

  /**
   * Overrides \Drupal\Core\Entity\Query\Sql\Query::result().
   *
   * @return array|int
   *   Returns the aggregated result, or a number if it's a count query.
   */
  protected function result() {
    if ($this->count) {
      return parent::result();
    }
    $return = [];
    foreach ($this->sqlQuery->execute() as $row) {
      $return[] = (array) $row;
    }
    return $return;
  }

}
