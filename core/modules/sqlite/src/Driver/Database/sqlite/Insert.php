<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\Query\Insert as QueryInsert;

/**
 * SQLite implementation of \Drupal\Core\Database\Query\Insert.
 *
 * We ignore all the default fields and use the clever SQLite syntax:
 *   INSERT INTO table DEFAULT VALUES
 * for degenerated "default only" queries.
 */
class Insert extends QueryInsert {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!$this->preExecute()) {
      return NULL;
    }

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      // The SelectQuery may contain arguments, load and pass them through.
      return $this->connection->query((string) $this, $this->fromQuery->getArguments(), $this->queryOptions);
    }

    // If there are any fields in the query, execute normal INSERT statements.
    if (count($this->insertFields)) {
      $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions);

      if (count($this->insertValues) === 1) {
        // Inserting a single row does not require a transaction to be atomic,
        // and executes faster without a transaction wrapper.
        $insert_values = $this->insertValues[0];
        try {
          $stmt->execute($insert_values, $this->queryOptions);
        }
        catch (\Exception $e) {
          $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, $insert_values, $this->queryOptions);
        }
      }
      else {
        // Inserting multiple rows requires a transaction to be atomic, and
        // executes faster as a single transaction.
        try {
          $transaction = $this->connection->startTransaction();
        }
        catch (\PDOException $e) {
          // $this->connection->exceptionHandler()->handleExecutionException()
          // requires a $statement argument, so we cannot use that.
          throw new DatabaseExceptionWrapper($e->getMessage(), 0, $e);
        }
        foreach ($this->insertValues as $insert_values) {
          try {
            $stmt->execute($insert_values, $this->queryOptions);
          }
          catch (\Exception $e) {
            // One of the INSERTs failed, rollback the whole batch.
            $transaction->rollBack();
            $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, $insert_values, $this->queryOptions);
          }
        }
      }
      // Re-initialize the values array so that we can re-use this query.
      $this->insertValues = [];
    }
    // If there are no fields in the query, execute an INSERT statement that
    // only populates default values.
    else {
      $stmt = $this->connection->prepareStatement("INSERT INTO {{$this->table}} DEFAULT VALUES", $this->queryOptions);
      try {
        $stmt->execute(NULL, $this->queryOptions);
      }
      catch (\Exception $e) {
        $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, [], $this->queryOptions);
      }
    }

    return $this->connection->lastInsertId();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Produce as many generic placeholders as necessary.
    $placeholders = [];
    if (!empty($this->insertFields)) {
      $placeholders = array_fill(0, count($this->insertFields), '?');
    }

    $insert_fields = array_map(function ($field) {
      return $this->connection->escapeField($field);
    }, $this->insertFields);

    // If we're selecting from a SelectQuery, finish building the query and
    // pass it back, as any remaining options are irrelevant.
    if (!empty($this->fromQuery)) {
      $insert_fields_string = $insert_fields ? ' (' . implode(', ', $insert_fields) . ') ' : ' ';
      return $comments . 'INSERT INTO {' . $this->table . '}' . $insert_fields_string . $this->fromQuery;
    }

    return $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
  }

}
