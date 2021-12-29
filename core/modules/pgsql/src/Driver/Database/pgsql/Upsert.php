<?php

namespace Drupal\pgsql\Driver\Database\pgsql;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

// cSpell:ignore nextval setval

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Upsert.
 */
class Upsert extends QueryUpsert {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }

    $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions, TRUE);

    // Fetch the list of blobs and sequences used on that table.
    $table_information = $this->connection->schema()->queryTableInformation($this->table);

    $max_placeholder = 0;
    $blobs = [];
    $blob_count = 0;
    foreach ($this->insertValues as $insert_values) {
      foreach ($this->insertFields as $idx => $field) {
        if (isset($table_information->blob_fields[$field]) && $insert_values[$idx] !== NULL) {
          $blobs[$blob_count] = fopen('php://memory', 'a');
          fwrite($blobs[$blob_count], $insert_values[$idx]);
          rewind($blobs[$blob_count]);

          $stmt->getClientStatement()->bindParam(':db_insert_placeholder_' . $max_placeholder++, $blobs[$blob_count], \PDO::PARAM_LOB);

          // Pre-increment is faster in PHP than increment.
          ++$blob_count;
        }
        else {
          $stmt->getClientStatement()->bindParam(':db_insert_placeholder_' . $max_placeholder++, $insert_values[$idx]);
        }
      }
      // Check if values for a serial field has been passed.
      if (!empty($table_information->serial_fields)) {
        foreach ($table_information->serial_fields as $index => $serial_field) {
          $serial_key = array_search($serial_field, $this->insertFields);
          if ($serial_key !== FALSE) {
            $serial_value = $insert_values[$serial_key];

            // Sequences must be greater than or equal to 1.
            if ($serial_value === NULL || !$serial_value) {
              $serial_value = 1;
            }
            // Set the sequence to the bigger value of either the passed
            // value or the max value of the column. It can happen that another
            // thread calls nextval() which could lead to a serial number being
            // used twice. However, trying to insert a value into a serial
            // column should only be done in very rare cases and is not thread
            // safe by definition.
            $this->connection->query("SELECT setval('" . $table_information->sequences[$index] . "', GREATEST(MAX(" . $serial_field . "), :serial_value)) FROM {" . $this->table . "}", [':serial_value' => (int) $serial_value]);
          }
        }
      }
    }

    $options = $this->queryOptions;
    if (!empty($table_information->sequences)) {
      $options['sequence_name'] = $table_information->sequences[0];
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = [];

    // Create a savepoint so we can rollback a failed query. This is so we can
    // mimic MySQL and SQLite transactions which don't fail if a single query
    // fails. This is important for tables that are created on demand. For
    // example, \Drupal\Core\Cache\DatabaseBackend.
    $this->connection->addSavepoint();
    try {
      $stmt->execute(NULL, $options);
      $this->connection->releaseSavepoint();
      return $stmt->rowCount();
    }
    catch (\Exception $e) {
      $this->connection->rollbackSavepoint();
      $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, [], $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);
    $insert_fields = array_map(function ($field) {
      return $this->connection->escapeField($field);
    }, $insert_fields);

    $query = $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

    $values = $this->getInsertPlaceholderFragment($this->insertValues, $this->defaultFields);
    $query .= implode(', ', $values);

    // Updating the unique / primary key is not necessary.
    unset($insert_fields[$this->key]);

    $update = [];
    foreach ($insert_fields as $field) {
      // The "excluded." prefix causes the field to refer to the value for field
      // that would have been inserted had there been no conflict.
      $update[] = "$field = EXCLUDED.$field";
    }

    $query .= ' ON CONFLICT (' . $this->connection->escapeField($this->key) . ') DO UPDATE SET ' . implode(', ', $update);

    return $query;
  }

}
