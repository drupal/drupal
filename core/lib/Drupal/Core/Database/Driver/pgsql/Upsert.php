<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\pgsql\Upsert.
 */

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * Implements the Upsert query for the PostgreSQL database driver.
 */
class Upsert extends QueryUpsert {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }

    // Default options for upsert queries.
    $this->queryOptions += array(
      'throw_exception' => TRUE,
    );

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);
    $insert_fields_escaped = array_map(function($f) { return $this->connection->escapeField($f); }, $insert_fields);

    $table = $this->connection->escapeTable($this->table);
    $unique_key = $this->connection->escapeField($this->key);

    // We have to execute multiple queries, therefore we wrap everything in a
    // transaction so that it is atomic where possible.
    $transaction = $this->connection->startTransaction();

    try {
      // First, create a temporary table with the same schema as the table we
      // are trying to upsert in. This results in the following query:
      //
      // CREATE TEMP TABLE temp_table AS SELECT * FROM table_name LIMIT 0;
      $query = 'SELECT * FROM {' . $table . '} LIMIT 0';
      $temp_table = $this->connection->queryTemporary($query, [], $this->queryOptions);

      // Second, insert the data in the temporary table.
      $insert = $this->connection->insert($temp_table, $this->queryOptions)
        ->fields($insert_fields);
      foreach ($this->insertValues as $insert_values) {
        $insert->values($insert_values);
      }
      $insert->execute();

      // Third, lock the table we're upserting into.
      $this->connection->query('LOCK TABLE {' . $table . '} IN EXCLUSIVE MODE', [], $this->queryOptions);

      // Fourth, update any rows that can be updated. This results in the
      // following query:
      //
      // UPDATE table_name
      // SET column1 = temp_table.column1 [, column2 = temp_table.column2, ...]
      // FROM temp_table
      // WHERE table_name.id = temp_table.id;
      $update = [];
      foreach ($insert_fields_escaped as $field) {
        if ($field !== $unique_key) {
          $update[] = "$field = {" . $temp_table . "}.$field";
        }
      }

      $update_query = 'UPDATE {' . $table . '} SET ' . implode(', ', $update);
      $update_query .= ' FROM {' . $temp_table . '}';
      $update_query .= ' WHERE {' . $temp_table . '}.' . $unique_key . ' = {' . $table . '}.' . $unique_key;
      $this->connection->query($update_query, [], $this->queryOptions);

      // Fifth, insert the remaining rows. This results in the following query:
      //
      // INSERT INTO table_name
      // SELECT temp_table.primary_key, temp_table.column1 [, temp_table.column2 ...]
      // FROM temp_table
      // LEFT OUTER JOIN table_name ON (table_name.id = temp_table.id)
      // WHERE table_name.id IS NULL;
      $select = $this->connection->select($temp_table, 'temp_table', $this->queryOptions)
        ->fields('temp_table', $insert_fields);
      $select->leftJoin($this->table, 'actual_table', 'actual_table.' . $this->key . ' = temp_table.' . $this->key);
      $select->isNull('actual_table.' . $this->key);

      $this->connection->insert($this->table, $this->queryOptions)
        ->from($select)
        ->execute();
    }
    catch (\Exception $e) {
      // One of the queries failed, rollback the whole batch.
      $transaction->rollback();

      // Rethrow the exception for the calling code.
      throw $e;
    }

    // Re-initialize the values array so that we can re-use this query.
    $this->insertValues = array();

    // Transaction commits here where $transaction looses scope.

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Nothing to do.
  }

}
