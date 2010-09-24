<?php
// $Id: query.inc,v 1.23 2010/09/24 21:24:13 webchick Exp $

/**
 * @ingroup database
 * @{
 */

/**
 * @file
 * Query code for PostgreSQL embedded database engine.
 */


class InsertQuery_pgsql extends InsertQuery {

  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }

    $stmt = $this->connection->prepareQuery((string) $this);

    // Fetch the list of blobs and sequences used on that table.
    $table_information = $this->connection->schema()->queryTableInformation($this->table);

    $max_placeholder = 0;
    $blobs = array();
    $blob_count = 0;
    foreach ($this->insertValues as $insert_values) {
      foreach ($this->insertFields as $idx => $field) {
        if (isset($table_information->blob_fields[$field])) {
          $blobs[$blob_count] = fopen('php://memory', 'a');
          fwrite($blobs[$blob_count], $insert_values[$idx]);
          rewind($blobs[$blob_count]);

          $stmt->bindParam(':db_insert_placeholder_' . $max_placeholder++, $blobs[$blob_count], PDO::PARAM_LOB);

          // Pre-increment is faster in PHP than increment.
          ++$blob_count;
        }
        else {
          $stmt->bindParam(':db_insert_placeholder_' . $max_placeholder++, $insert_values[$idx]);
        }
      }
      // Check if values for a serial field has been passed.
      if (!empty($table_information->serial_fields)) {
        foreach ($table_information->serial_fields as $index => $serial_field) {
          $serial_key = array_search($serial_field, $this->insertFields);
          if ($serial_key !== FALSE) {
            $serial_value = $insert_values[$serial_key];

            // Force $last_insert_id to the specified value. This is only done
            // if $index is 0.
            if ($index == 0) {
              $last_insert_id = $serial_value;
            }
            // Set the sequence to the bigger value of either the passed
            // value or the max value of the column. It can happen that another
            // thread calls nextval() which could lead to a serial number being
            // used twice. However, trying to insert a value into a serial
            // column should only be done in very rare cases and is not thread
            // safe by definition.
            $this->connection->query("SELECT setval('" . $table_information->sequences[$index] . "', GREATEST(MAX(" . $serial_field . "), :serial_value)) FROM {" . $this->table . "}", array(':serial_value' => (int)$serial_value));
          }
        }
      }
    }
    if (!empty($this->fromQuery)) {
      // bindParam stores only a reference to the variable that is followed when
      // the statement is executed. We pass $arguments[$key] instead of $value
      // because the second argument to bindParam is passed by reference and
      // the foreach statement assigns the element to the existing reference.
      $arguments = $this->fromQuery->getArguments();
      foreach ($arguments as $key => $value) {
        $stmt->bindParam($key, $arguments[$key]);
      }
    }

    // PostgreSQL requires the table name to be specified explicitly
    // when requesting the last insert ID, so we pass that in via
    // the options array.
    $options = $this->queryOptions;

    if (!empty($table_information->sequences)) {
      $options['sequence_name'] = $table_information->sequences[0];
    }
    // If there are no sequences then we can't get a last insert id.
    elseif ($options['return'] == Database::RETURN_INSERT_ID) {
      $options['return'] = Database::RETURN_NULL;
    }
    // Only use the returned last_insert_id if it is not already set.
    if (!empty($last_insert_id)) {
      $this->connection->query($stmt, array(), $options);
    }
    else {
      $last_insert_id = $this->connection->query($stmt, array(), $options);
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = array();

    return $last_insert_id;
  }

  public function __toString() {
    // Create a comments string to prepend to the query.
    $comments = (!empty($this->comments)) ? '/* ' . implode('; ', $this->comments) . ' */ ' : '';

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') ' . $this->fromQuery;
    }

    $query = $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

    $max_placeholder = 0;
    $values = array();
      if (count($this->insertValues)) {
      foreach ($this->insertValues as $insert_values) {
        $placeholders = array();

        // Default fields aren't really placeholders, but this is the most convenient
        // way to handle them.
        $placeholders = array_pad($placeholders, count($this->defaultFields), 'default');

        $new_placeholder = $max_placeholder + count($insert_values);
        for ($i = $max_placeholder; $i < $new_placeholder; ++$i) {
          $placeholders[] = ':db_insert_placeholder_' . $i;
        }
        $max_placeholder = $new_placeholder;
        $values[] = '(' . implode(', ', $placeholders) . ')';
      }
    }
    else {
      // If there are no values, then this is a default-only query. We still need to handle that.
      $placeholders = array_fill(0, count($this->defaultFields), 'default');
      $values[] = '(' . implode(', ', $placeholders) . ')';
    }

    $query .= implode(', ', $values);

    return $query;
  }
}

class UpdateQuery_pgsql extends UpdateQuery {
  public function execute() {
    $max_placeholder = 0;
    $blobs = array();
    $blob_count = 0;

    // Because we filter $fields the same way here and in __toString(), the
    // placeholders will all match up properly.
    $stmt = $this->connection->prepareQuery((string) $this);

    // Fetch the list of blobs and sequences used on that table.
    $table_information = $this->connection->schema()->queryTableInformation($this->table);

    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    $fields = $this->fields;
    $expression_fields = array();
    foreach ($this->expressionFields as $field => $data) {
      if (!empty($data['arguments'])) {
        foreach ($data['arguments'] as $placeholder => $argument) {
          // We assume that an expression will never happen on a BLOB field,
          // which is a fairly safe assumption to make since in most cases
          // it would be an invalid query anyway.
          $stmt->bindParam($placeholder, $data['arguments'][$placeholder]);
        }
      }
      unset($fields[$field]);
    }

    foreach ($fields as $field => $value) {
      $placeholder = ':db_update_placeholder_' . ($max_placeholder++);

      if (isset($table_information->blob_fields[$field])) {
        $blobs[$blob_count] = fopen('php://memory', 'a');
        fwrite($blobs[$blob_count], $value);
        rewind($blobs[$blob_count]);
        $stmt->bindParam($placeholder, $blobs[$blob_count], PDO::PARAM_LOB);
        ++$blob_count;
      }
      else {
        $stmt->bindParam($placeholder, $fields[$field]);
      }
    }

    if (count($this->condition)) {
      $this->condition->compile($this->connection, $this);

      $arguments = $this->condition->arguments();
      foreach ($arguments as $placeholder => $value) {
        $stmt->bindParam($placeholder, $arguments[$placeholder]);
      }
    }

    $options = $this->queryOptions;
    $options['already_prepared'] = TRUE;
    $this->connection->query($stmt, $options);

    return $stmt->rowCount();
  }
}

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
