<?php
// $Id$

/**
 * @file
 * Query code for SQLite embedded database engine.
 */

/**
 * @ingroup database
 * @{
 */

/**
 * SQLite specific implementation of InsertQuery.
 *
 * We ignore all the default fields and use the clever SQLite syntax:
 *   INSERT INTO table DEFAULT VALUES
 * for degenerated "default only" queries.
 */
class InsertQuery_sqlite extends InsertQuery {

  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }
    if (count($this->insertFields)) {
      return parent::execute();
    }
    else {
      return $this->connection->query('INSERT INTO {' . $this->table . '} DEFAULT VALUES', array(), $this->queryOptions);
    }
  }

  public function __toString() {
    // Create a comments string to prepend to the query.
    $comments = (!empty($this->comments)) ? '/* ' . implode('; ', $this->comments) . ' */ ' : '';

    // Produce as many generic placeholders as necessary.
    $placeholders = array_fill(0, count($this->insertFields), '?');

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $this->insertFields) . ') ' . $this->fromQuery;
    }

    return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $this->insertFields) . ') VALUES (' . implode(', ', $placeholders) . ')';
  }

}

/**
 * SQLite specific implementation of UpdateQuery.
 *
 * SQLite counts all the rows that match the conditions as modified, even if they
 * will not be affected by the query. We workaround this by ensuring that
 * we don't select those rows.
 *
 * A query like this one:
 *   UPDATE test SET name = 'newname' WHERE tid = 1
 * will become:
 *   UPDATE test SET name = 'newname' WHERE tid = 1 AND name <> 'newname'
 */
class UpdateQuery_sqlite extends UpdateQuery {
  /**
   * Helper function that removes the fields that are already in a condition.
   *
   * @param $fields
   *   The fields.
   * @param QueryConditionInterface $condition
   *   A database condition.
   */
  protected function removeFieldsInCondition(&$fields, QueryConditionInterface $condition) {
    foreach ($condition->conditions() as $child_condition) {
      if ($child_condition['field'] instanceof QueryConditionInterface) {
        $this->removeFieldsInCondition($fields, $child_condition['field']);
      }
      else {
        unset($fields[$child_condition['field']]);
      }
    }
  }

  public function execute() {
    if (!empty($this->queryOptions['sqlite_return_matched_rows'])) {
      return parent::execute();
    }

    // Get the fields used in the update query, and remove those that are already
    // in the condition.
    $fields = $this->expressionFields + $this->fields;
    $this->removeFieldsInCondition($fields, $this->condition);

    // Add the inverse of the fields to the condition.
    $condition = new DatabaseCondition('OR');
    foreach ($fields as $field => $data) {
      if (is_array($data)) {
        // The field is an expression.
        $condition->condition($field, $data['expression'], '<>');
        $condition->isNull($field);
      }
      elseif (is_null($data)) {
        // The field will be set to NULL.
        $condition->isNull($field);
      }
      else {
        $condition->condition($field, $data, '<>');
        $condition->isNull($field);
      }
    }
    if (count($condition)) {
      $condition->compile($this->connection, $this);
      $this->condition->where((string) $condition, $condition->arguments());
    }
    return parent::execute();
  }

}

/**
 * SQLite specific implementation of MergeQuery.
 *
 * SQLite doesn't support row-level locking, but acquire locks on the whole
 * database file. We implement MergeQuery using a different strategy:
 *   - UPDATE xxx WHERE <key condition>
 *   - if the previous query hasn't matched, INSERT
 *
 * The first UPDATE query will acquire a RESERVED lock on the database.
 */
class MergeQuery_sqlite extends MergeQuery {
  public function execute() {
    // If validation fails, simply return NULL.
    // Note that validation routines in preExecute() may throw exceptions instead.
    if (!$this->preExecute()) {
      return NULL;
    }

    // Wrap multiple queries in a transaction.
    $transaction = $this->connection->startTransaction();

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

    // The update fields are empty, fill them with dummy data.
    if (!$update_fields && !$this->expressionFields) {
      $update_fields = array_slice($this->keyFields, 0, 1);
    }

    // Start with an update query, this acquires a RESERVED lock on the database.
    // Use the SQLite-specific 'sqlite_return_matched_rows' query option to
    // return the number of rows matched by that query, not modified by it.
    $update = $this->connection->update($this->table, array('sqlite_return_matched_rows' => TRUE) + $this->queryOptions)->fields($update_fields);

    foreach ($this->keyFields as $field => $value) {
      $update->condition($field, $value);
    }
    foreach ($this->expressionFields as $field => $expression) {
      $update->expression($field, $expression['expression'], $expression['arguments']);
    }
    if ($update->execute()) {
      return MergeQuery::STATUS_UPDATE;
    }

    // The UPDATE query failed to match rows, proceed with an INSERT.
    $insert_fields = $this->insertFields + $this->keyFields;
    $this->connection->insert($this->table, $this->queryOptions)->fields($insert_fields)->execute();
    return MergeQuery::STATUS_INSERT;
  }
}

/**
 * SQLite specific implementation of DeleteQuery.
 *
 * When the WHERE is omitted from a DELETE statement and the table being deleted
 * has no triggers, SQLite uses an optimization to erase the entire table content
 * without having to visit each row of the table individually.
 *
 * Prior to SQLite 3.6.5, SQLite does not return the actual number of rows deleted
 * by that optimized "truncate" optimization.
 */
class DeleteQuery_sqlite extends DeleteQuery {
  public function execute() {
    if (!count($this->condition)) {
      $total_rows = $this->connection->query('SELECT COUNT(*) FROM {' . $this->connection->escapeTable($this->table) . '}')->fetchField();
      parent::execute();
      return $total_rows;
    }
    else {
      return parent::execute();
    }
  }
}

/**
 * SQLite specific implementation of TruncateQuery.
 *
 * SQLite doesn't support TRUNCATE, but a DELETE query with no condition has
 * exactly the effect (it is implemented by DROPing the table).
 */
class TruncateQuery_sqlite extends TruncateQuery {
  public function __toString() {
    // Create a comments string to prepend to the query.
    $comments = (!empty($this->comments)) ? '/* ' . implode('; ', $this->comments) . ' */ ' : '';

    return $comments . 'DELETE FROM {' . $this->connection->escapeTable($this->table) . '} ';
  }
}

/**
 * @} End of "ingroup database".
 */
