<?php
// $Id $

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
    if (count($this->insertFields) + count($this->defaultFields) == 0) {
      return NULL;
    }
    if (count($this->insertFields)) {
      return parent::execute();
    }
    else {
      return $this->connection->query('INSERT INTO {'. $this->table .'} DEFAULT VALUES', array(), $this->queryOptions);
    }
  }

  public function __toString() {
    // Produce as many generic placeholders as necessary.
    $placeholders = array_fill(0, count($this->insertFields), '?');
    return 'INSERT INTO {'. $this->table .'} ('. implode(', ', $this->insertFields) .') VALUES ('. implode(', ', $placeholders) .')';
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
    // Get the fields used in the update query, and remove those that are already
    // in the condition.
    $fields = $this->expressionFields + $this->fields;
    $this->removeFieldsInCondition($fields, $this->condition);

    // Add the inverse of the fields to the condition.
    $condition = db_or();
    foreach ($fields as $field => $data) {
      if (is_array($data)) {
        // The field is an expression.
        $condition->condition($field, $data['expression'], '<>');
        // The IS NULL operator is badly managed by DatabaseCondition.
        $condition->where($field . ' IS NULL');
      }
      else if (is_null($data)) {
        // The field will be set to NULL.
        // The IS NULL operator is badly managed by DatabaseCondition.
        $condition->where($field . ' IS NOT NULL');
      }
      else {
        $condition->condition($field, $data, '<>');
        // The IS NULL operator is badly managed by DatabaseCondition.
        $condition->where($field . ' IS NULL');
      }
    }
    if (count($condition)) {
      $condition->compile($this->connection);
      $this->condition->where((string) $condition, $condition->arguments());
    }
    return parent::execute();
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
 * @} End of "ingroup database".
 */
