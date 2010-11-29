<?php
// $Id$

/**
 * @file
 * Select builder for PostgreSQL database engine.
 */

/**
 * @ingroup database
 * @{
 */

class SelectQuery_pgsql extends SelectQuery {

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
   *   $query = db_select('node', 'n');
   *   $query->join('node_revision', 'nr', 'n.vid = nr.vid');
   *   $query
   *     ->distinct()
   *     ->fields('n')
   *     ->orderBy('timestamp');
   * @endcode
   *
   * In this query, it is not possible (without relying on the schema) to know
   * whether timestamp belongs to node_revisions and needs to be added or
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
    // Call parent function to order on this.
    $return = parent::orderBy($field, $direction);

    // If there is a table alias specified, split it up.
    if (strpos($field, '.') !== FALSE) {
      list($table, $table_field) = explode('.', $field);
    }
    // Figure out if the field has already been added.
    foreach ($this->fields as $existing_field) {
      if (!empty($table)) {
        // If table alias is given, check if field and table exists.
        if ($existing_field['table'] == $table && $existing_field['field'] == $table_field) {
          return $return;
        }
      }
      else {
        // If there is no table, simply check if the field exists as a field or
        // an aliased field.
        if ($existing_field['alias'] == $field) {
          return $return;
        }
      }
    }

    // Also check expression aliases.
    foreach ($this->expressions as $expression) {
      if ($expression['alias'] == $field) {
        return $return;
      }
    }

    // If a table loads all fields, it can not be added again. It would
    // result in an ambigious alias error because that field would be loaded
    // twice: Once through table_alias.* and once directly. If the field
    // actually belongs to a different table, it must be added manually.
    foreach ($this->tables as $table) {
      if (!empty($table['all_fields'])) {
        return $return;
      }
    }

    // If $field contains an characters which are not allowed in a field name
    // it is considered an expression, these can't be handeld automatically
    // either.
    if ($this->connection->escapeField($field) != $field) {
      return $return;
    }

    // This is a case that can be handled automatically, add the field.
    $this->addField(NULL, $field);
    return $return;
  }
}

/**
 * @} End of "ingroup database".
 */

