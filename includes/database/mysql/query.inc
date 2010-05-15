<?php
// $Id$

/**
 * @ingroup database
 * @{
 */

/**
 * @file
 * Query code for MySQL embedded database engine.
 */


class InsertQuery_mysql extends InsertQuery {

  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (empty($this->fromQuery)) {
      $max_placeholder = 0;
      $values = array();
      foreach ($this->insertValues as $insert_values) {
        foreach ($insert_values as $value) {
          $values[':db_insert_placeholder_' . $max_placeholder++] = $value;
        }
      }
    }
    else {
      $values = $this->fromQuery->getArguments();
    }

    $last_insert_id = $this->connection->query((string) $this, $values, $this->queryOptions);

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

class MergeQuery_mysql extends MergeQuery {

  public function execute() {

    // A merge query without any key field is invalid.
    if (count($this->keyFields) == 0) {
      throw new InvalidMergeQueryException("You need to specify key fields before executing a merge query");
    }

    // Set defaults.
    if ($this->updateFields) {
      $update_fields = $this->updateFields;
    }
    else {
      // When update fields are derived from insert fields, we don't need
      // placeholders since we can tell MySQL to reuse insert supplied
      // values using the VALUES(col_name) function.
      $update_fields = array();
    }

    $insert_fields = $this->insertFields + $this->keyFields;

    $max_placeholder = 0;
    $values = array();
    // We assume that the order here is the same as in __toString(). If that's
    // not the case, then we have serious problems.
    foreach ($insert_fields as $value) {
      $values[':db_insert_placeholder_' . $max_placeholder++] = $value;
    }

    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    foreach ($this->expressionFields as $field => $data) {
      if (!empty($data['arguments'])) {
        $values += $data['arguments'];
      }
      unset($update_fields[$field]);
    }

    // Because we filter $fields the same way here and in __toString(), the
    // placeholders will all match up properly.
    $max_placeholder = 0;
    foreach ($update_fields as $field => $value) {
      $values[':db_update_placeholder_' . ($max_placeholder++)] = $value;
    }


    // MySQL's INSERT ... ON DUPLICATE KEY UPDATE queries return 1
    // (MergeQuery::STATUS_INSERT) for an INSERT operation or 2
    // (MergeQuery::STATUS_UPDATE) for an UPDATE operation.
    //
    // @link http ://dev.mysql.com/doc/refman/5.0/en/mysql-affected-rows.html
    $this->queryOptions['return'] = Database::RETURN_AFFECTED;
    return $this->connection->query((string) $this, $values, $this->queryOptions);
  }


  public function __toString() {
    // Create a comments string to prepend to the query.
    $comments = (!empty($this->comments)) ? '/* ' . implode('; ', $this->comments) . ' */ ' : '';

    // Set defaults.
    if ($this->updateFields) {
      $update_fields = $this->updateFields;
    }
    else {
      $update_fields = $this->insertFields;
      // If there are no exclude fields, this is a no-op.
      foreach ($this->excludeFields as $exclude_field) {
        unset($update_fields[$exclude_field]);
      }
    }

    // If the merge query has no fields to update, add the first key as an
    // update field so the query will not fail if a duplicate key is found.
    if (!$update_fields && !$this->expressionFields) {
      $update_fields = array_slice($this->keyFields, 0, 1, TRUE);
    }

    $insert_fields = $this->insertFields + $this->keyFields;

    $query = $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', array_keys($insert_fields)) . ') VALUES ';

    $max_placeholder = 0;
    $values = array();
    // We don't need the $field, but this is a convenient way to count.
    foreach ($insert_fields as $field) {
      $values[] = ':db_insert_placeholder_' . $max_placeholder++;
    }

    $query .= '(' . implode(', ', $values) . ') ON DUPLICATE KEY UPDATE ';

    // Expressions take priority over literal fields, so we process those first
    // and remove any literal fields that conflict.
    $max_placeholder = 0;
    $update = array();
    foreach ($this->expressionFields as $field => $data) {
      $update[] = $field . '=' . $data['expression'];
      unset($update_fields[$field]);
    }

    // Build update fields clauses based on caller supplied list, or derived
    // from insert supplied values using the VALUES(col_name) function.
    foreach ($update_fields as $field => $value) {
      if ($this->updateFields) {
        $update[] = ($field . '=:db_update_placeholder_' . $max_placeholder++);
      }
      else {
        $update[] = ($field . '=VALUES(' . $field . ')');
      }
    }

    $query .= implode(', ', $update);

    return $query;
  }
}

/**
 * @} End of "ingroup database".
 */
