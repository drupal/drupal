<?php
// $Id$


/**
 * @ingroup database
 * @{
 */

class InsertQuery_pgsql extends InsertQuery {

  public function __construct($connection, $table, Array $options = array()) {
    parent::__construct($connection, $table, $options);
    $this->queryOptions['return'] = Database::RETURN_NULL;
  }

  public function execute() {

    // Confirm that the user did not try to specify an identical
    //  field and default field.
    if (array_intersect($this->insertFields, $this->defaultFields)) {
      throw new PDOException('You may not specify the same field to have a value and a schema-default value.');
    }

    if (count($this->insertFields) + count($this->defaultFields) == 0) {
      return NULL;
    }

    $schema = drupal_get_schema($this->table);

    $stmt = $this->connection->prepareQuery((string)$this);

    $max_placeholder = 0;
    $blobs = array();
    $blob_cnt = 0;
    foreach ($this->insertValues as &$insert_values) {
      foreach ($this->insertFields as $idx => $field) {
        switch ($schema['fields'][$field]['type']) {
          case 'blob':
            $blobs[$blob_cnt] = fopen('php://memory', 'a');
            fwrite($blobs[$blob_cnt], $insert_values[$idx]);
            rewind($blobs[$blob_cnt]);

            $stmt->bindParam(':db_insert_placeholder_' . $max_placeholder++, $blobs[$blob_cnt], PDO::PARAM_LOB);

            ++$blob_cnt;

            break;
          default:
            $stmt->bindParam(':db_insert_placeholder_'. $max_placeholder++, $insert_values[$idx]);
            break;
        }
      }
    }

    // PostgreSQL requires the table name to be specified explicitly
    // when requesting the last insert ID, so we pass that in via
    // the options array.
    $options = $this->queryOptions;

    if ($schema['fields'][$schema['primary key'][0]]['type'] == 'serial') {
      $options['sequence_name'] = $this->connection->makeSequenceName($this->table, $schema['primary key'][0]);
      $options['return'] = Database::RETURN_INSERT_ID;
    }
    $last_insert_id = $this->connection->query($stmt, array(), $options);

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = array();

    return $last_insert_id;
  }

  public function __toString() {

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);

    $query = "INSERT INTO {" . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

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
      // If there are no values, then this is a default-only query.  We still need to handle that.
      $placeholders = array_fill(0, count($this->defaultFields), 'default');
      $values[] = '(' . implode(', ', $placeholders) .')';
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

    $schema = drupal_get_schema($this->table);

    // Because we filter $fields the same way here and in __toString(), the
    // placeholders will all match up properly.
    $stmt = $this->connection->prepareQuery((string)$this);

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
          $stmt->bindParam($placeholder, $argument);
        }
      }
      unset($fields[$field]);
    }

    foreach ($fields as $field => &$value) {
      $placeholder = ':db_update_placeholder_' . ($max_placeholder++);

      switch ($schema['fields'][$field]['type']) {
        case 'blob':
          $blobs[$blob_count] = fopen('php://memory', 'a');
          fwrite($blobs[$blob_count], $value);
          rewind($blobs[$blob_count]);
          $stmt->bindParam($placeholder, $blobs[$blob_count], PDO::PARAM_LOB);
          ++$blob_count;
          break;
        default:
          $stmt->bindParam($placeholder, $value);
          break;
      }
    }

    if (count($this->condition)) {
      $this->condition->compile($this->connection);

      $arguments = $this->condition->arguments();
      foreach ($arguments as $placeholder => &$value) {
        $stmt->bindParam($placeholder, $value);
      }
    }

    $options = $this->queryOptions;
    $options['already_prepared'] = TRUE;
    $this->connection->query($stmt, $options);

    //$stmt->execute(NULL, $this->queryOptions);
    return $stmt->rowCount();
  }
}
