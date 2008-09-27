<?php
// $Id$

/**
 * @ingroup database
 * @{
 */

class InsertQuery_mysql extends InsertQuery {

  public function execute() {

    // Confirm that the user did not try to specify an identical
    //  field and default field.
    if (array_intersect($this->insertFields, $this->defaultFields)) {
      throw new PDOException('You may not specify the same field to have a value and a schema-default value.');
    }

    $last_insert_id = 0;

    $max_placeholder = 0;
    $values = array();
    foreach ($this->insertValues as $insert_values) {
      foreach ($insert_values as $value) {
        $values[':db_insert_placeholder_' . $max_placeholder++] = $value;
      }
    }

    $last_insert_id = $this->connection->query((string)$this, $values, $this->queryOptions);

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = array();

    return $last_insert_id;
  }

  public function __toString() {

    $delay = $this->queryOptions['delay'] ? 'DELAYED' : '';

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);

    $query = "INSERT $delay INTO {" . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

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
          $placeholders[] = ':db_insert_placeholder_'. $i;
        }
        $max_placeholder = $new_placeholder;
        $values[] = '('. implode(', ', $placeholders) .')';
      }
    }
    else {
      // If there are no values, then this is a default-only query.  We still need to handle that.
      $placeholders = array_fill(0, count($this->defaultFields), 'default');
      $values[] = '(' . implode(', ', $placeholders) .')';
    }

    $query .= implode(', ', $values);

    return $query;
  }
}

class MergeQuery_mysql extends MergeQuery {

  public function execute() {
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
    // We assume that the order here is the same as in __toString().  If that's
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

    $last_insert_id = $this->connection->query((string)$this, $values, $this->queryOptions);

    return $last_insert_id;
  }


  public function __toString() {

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

    $insert_fields = $this->insertFields + $this->keyFields;

    $query = "INSERT INTO {" . $this->table . '} (' . implode(', ', array_keys($insert_fields)) . ') VALUES ';

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
