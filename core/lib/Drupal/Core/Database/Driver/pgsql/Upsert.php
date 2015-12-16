<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Driver\pgsql\Upsert.
 */

namespace Drupal\Core\Database\Driver\pgsql;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

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

    // Default options for upsert queries.
    $this->queryOptions += array(
      'throw_exception' => TRUE,
    );

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);

    $table = $this->connection->escapeTable($this->table);

    // We have to execute multiple queries, therefore we wrap everything in a
    // transaction so that it is atomic where possible.
    $transaction = $this->connection->startTransaction();

    try {
      // First, lock the table we're upserting into.
      $this->connection->query('LOCK TABLE {' . $table . '} IN SHARE ROW EXCLUSIVE MODE', [], $this->queryOptions);

      // Second, delete all items first so we can do one insert.
      $unique_key_position = array_search($this->key, $insert_fields);
      $delete_ids = [];
      foreach ($this->insertValues as $insert_values) {
        $delete_ids[] = $insert_values[$unique_key_position];
      }

      // Delete in chunks when a large array is passed.
      foreach (array_chunk($delete_ids, 1000) as $delete_ids_chunk) {
        $this->connection->delete($this->table, $this->queryOptions)
          ->condition($this->key, $delete_ids_chunk, 'IN')
          ->execute();
      }

      // Third, insert all the values.
      $insert = $this->connection->insert($this->table, $this->queryOptions)
        ->fields($insert_fields);
      foreach ($this->insertValues as $insert_values) {
        $insert->values($insert_values);
      }
      $insert->execute();
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
