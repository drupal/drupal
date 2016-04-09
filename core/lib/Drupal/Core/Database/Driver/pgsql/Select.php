<?php

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Query\Select as QuerySelect;

/**
 * @addtogroup database
 * @{
 */

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Select.
 */
class Select extends QuerySelect {

  public function orderRandom() {
    $alias = $this->addExpression('RANDOM()', 'random_field');
    $this->orderBy($alias);
    return $this;
  }

  /**
   * Overrides SelectQuery::orderBy().
   *
   * PostgreSQL adheres strictly to the SQL-92 standard and requires that when
   * using DISTINCT or GROUP BY conditions, fields and expressions that are
   * ordered on also need to be selected. This is a best effort implementation
   * to handle the cases that can be automated by adding the field if it is not
   * yet selected.
   *
   * @code
   *   $query = db_select('example', 'e');
   *   $query->join('example_revision', 'er', 'e.vid = er.vid');
   *   $query
   *     ->distinct()
   *     ->fields('e')
   *     ->orderBy('timestamp');
   * @endcode
   *
   * In this query, it is not possible (without relying on the schema) to know
   * whether timestamp belongs to example_revision and needs to be added or
   * belongs to node and is already selected. Queries like this will need to be
   * corrected in the original query by adding an explicit call to
   * SelectQuery::addField() or SelectQuery::fields().
   *
   * Since this has a small performance impact, both by the additional
   * processing in this function and in the database that needs to return the
   * additional fields, this is done as an override instead of implementing it
   * directly in SelectQuery::orderBy().
   */
  public function orderBy($field, $direction = 'ASC') {
    // Only allow ASC and DESC, default to ASC.
    // Emulate MySQL default behavior to sort NULL values first for ascending,
    // and last for descending.
    // @see http://www.postgresql.org/docs/9.3/static/queries-order.html
    $direction = strtoupper($direction) == 'DESC' ? 'DESC NULLS LAST' : 'ASC NULLS FIRST';
    $this->order[$field] = $direction;

    if ($this->hasTag('entity_query')) {
      return $this;
    }

    // If there is a table alias specified, split it up.
    if (strpos($field, '.') !== FALSE) {
      list($table, $table_field) = explode('.', $field);
    }
    // Figure out if the field has already been added.
    foreach ($this->fields as $existing_field) {
      if (!empty($table)) {
        // If table alias is given, check if field and table exists.
        if ($existing_field['table'] == $table && $existing_field['field'] == $table_field) {
          return $this;
        }
      }
      else {
        // If there is no table, simply check if the field exists as a field or
        // an aliased field.
        if ($existing_field['alias'] == $field) {
          return $this;
        }
      }
    }

    // Also check expression aliases.
    foreach ($this->expressions as $expression) {
      if ($expression['alias'] == $this->connection->escapeAlias($field)) {
        return $this;
      }
    }

    // If a table loads all fields, it can not be added again. It would
    // result in an ambiguous alias error because that field would be loaded
    // twice: Once through table_alias.* and once directly. If the field
    // actually belongs to a different table, it must be added manually.
    foreach ($this->tables as $table) {
      if (!empty($table['all_fields'])) {
        return $this;
      }
    }

    // If $field contains an characters which are not allowed in a field name
    // it is considered an expression, these can't be handled automatically
    // either.
    if ($this->connection->escapeField($field) != $field) {
      return $this;
    }

    // This is a case that can be handled automatically, add the field.
    $this->addField(NULL, $field);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addExpression($expression, $alias = NULL, $arguments = array()) {
    if (empty($alias)) {
      $alias = 'expression';
    }

    // This implements counting in the same manner as the parent method.
    $alias_candidate = $alias;
    $count = 2;
    while (!empty($this->expressions[$alias_candidate])) {
      $alias_candidate = $alias . '_' . $count++;
    }
    $alias = $alias_candidate;

    $this->expressions[$alias] = array(
      'expression' => $expression,
      'alias' => $this->connection->escapeAlias($alias_candidate),
      'arguments' => $arguments,
    );

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $this->connection->addSavepoint();
    try {
      $result = parent::execute();
    }
    catch (\Exception $e) {
      $this->connection->rollbackSavepoint();
      throw $e;
    }
    $this->connection->releaseSavepoint();

    return $result;
  }
}

/**
 * @} End of "addtogroup database".
 */
