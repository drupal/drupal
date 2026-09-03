<?php

namespace Drupal\pgsql\Driver\Database\pgsql;

use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Database\Query\InvalidMergeQueryException;
use Drupal\Core\Database\Query\Merge as QueryMerge;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * PostgreSQL implementation of \Drupal\Core\Database\Query\Merge.
 *
 * Executes the merge query as a single native MERGE statement instead of the
 * generic emulation with a SELECT followed by an INSERT or an UPDATE. The
 * RETURNING merge_action() clause reports whether an INSERT or an UPDATE was
 * performed, so execute() can still return the corresponding status code.
 *
 * The generic emulation of the parent class is used instead when the query
 * cannot be expressed as a native MERGE statement or would not benefit from
 * it:
 * - the condition targets a table other than the merge table, so the ON
 *   clause cannot be built from it;
 * - there are no fields to insert, in which case a WHEN NOT MATCHED THEN
 *   INSERT clause cannot be generated;
 * - values are inserted into serial fields, because the generic insert path
 *   takes care of synchronizing the corresponding sequences afterwards.
 *
 * A native MERGE is not immune to race conditions under the default READ
 * COMMITTED isolation level: a concurrent transaction can insert the same row
 * after the match phase, causing an integrity constraint violation. When that
 * happens the statement is retried once, so the racing row is matched and
 * updated instead.
 */
class Merge extends QueryMerge {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    if (!count($this->condition)) {
      throw new InvalidMergeQueryException('Invalid merge query: no conditions');
    }

    if (!$this->isNativeMerge()) {
      return parent::execute();
    }

    // Fetch the list of blobs and sequences used on that table.
    $table_information = $this->connection->schema()->queryTableInformation($this->table);

    // Inserting values into serial fields requires synchronizing the
    // sequence afterwards, which the generic insert path takes care of.
    if (!empty($table_information->serial_fields) && array_intersect($table_information->serial_fields, array_keys($this->insertFields))) {
      return parent::execute();
    }

    try {
      return $this->executeNativeMerge($table_information);
    }
    catch (IntegrityConstraintViolationException) {
      // The merge query failed. Maybe it's because a racing insert query
      // beat us in inserting the same row. Retry once: the racing row will
      // now be matched and updated instead.
      return $this->executeNativeMerge($table_information);
    }
  }

  /**
   * Determines whether the query executes as a single native MERGE statement.
   *
   * The native MERGE statement can only be used when the condition targets
   * the merge table itself and when there is something to insert. In all
   * other cases the generic SELECT + INSERT/UPDATE emulation is used.
   *
   * @return bool
   *   TRUE if the query executes as a native MERGE statement, FALSE if it
   *   uses the generic emulation.
   */
  protected function isNativeMerge(): bool {
    return $this->conditionTable === $this->table && ($this->insertFields || $this->defaultFields);
  }

  /**
   * Executes the merge as a single native MERGE statement.
   *
   * @param object $table_information
   *   The table information for the merge table, containing the blob fields.
   *
   * @return int|null
   *   One of Merge::STATUS_INSERT, Merge::STATUS_UPDATE or NULL if the row
   *   already existed and no update was requested.
   */
  protected function executeNativeMerge(object $table_information): ?int {
    $stmt = $this->connection->prepareStatement((string) $this, $this->queryOptions, TRUE);
    $client_statement = $stmt->getClientStatement();
    $blobs = [];
    $blob_count = 0;
    $bind = function ($placeholder, $value, $field = NULL) use ($client_statement, $table_information, &$blobs, &$blob_count) {
      if ($field !== NULL && isset($table_information->blob_fields[$field]) && $value !== NULL) {
        $blobs[$blob_count] = fopen('php://memory', 'a');
        fwrite($blobs[$blob_count], $value);
        rewind($blobs[$blob_count]);
        $client_statement->bindParam($placeholder, $blobs[$blob_count], \PDO::PARAM_LOB);
        ++$blob_count;
      }
      else {
        $client_statement->bindValue($placeholder, $value);
      }
    };

    // Arguments of the WHEN MATCHED THEN UPDATE clause. Expressions take
    // priority over literal fields, matching the order in __toString().
    if ($this->needsUpdate) {
      foreach ($this->expressionFields as $data) {
        if (!empty($data['arguments'])) {
          foreach ($data['arguments'] as $placeholder => $argument) {
            $bind($placeholder, $argument);
          }
        }
        if ($data['expression'] instanceof SelectInterface) {
          $data['expression']->compile($this->connection, $this);
          foreach ($data['expression']->arguments() as $placeholder => $argument) {
            $bind($placeholder, $argument);
          }
        }
      }
      $max_placeholder = 0;
      foreach (array_diff_key($this->updateFields, $this->expressionFields) as $field => $value) {
        $bind(':db_update_placeholder_' . ($max_placeholder++), $value, $field);
      }
    }

    // Arguments of the WHEN NOT MATCHED THEN INSERT clause.
    $max_placeholder = 0;
    foreach ($this->insertFields as $field => $value) {
      $bind(':db_insert_placeholder_' . ($max_placeholder++), $value, $field);
    }

    // Arguments of the ON condition.
    $this->condition->compile($this->connection, $this);
    foreach ($this->condition->arguments() as $placeholder => $value) {
      $bind($placeholder, $value);
    }

    // Create a savepoint so we can rollback a failed query. This is so we can
    // mimic MySQL and SQLite transactions which don't fail if a single query
    // fails. This is important for tables that are created on demand. For
    // example, \Drupal\Core\Cache\DatabaseBackend.
    if ($this->connection->inTransaction()) {
      $savepoint = $this->connection->startTransaction('mimic_implicit_commit');
    }
    try {
      $stmt->execute(NULL, $this->queryOptions);
      if (isset($savepoint)) {
        $savepoint->commitOrRelease();
      }
    }
    catch (\Exception $e) {
      if (isset($savepoint)) {
        $savepoint->rollback();
      }
      $this->connection->exceptionHandler()->handleExecutionException($e, $stmt, [], $this->queryOptions);
    }

    return match ($stmt->fetchField()) {
      'INSERT' => self::STATUS_INSERT,
      'UPDATE' => self::STATUS_UPDATE,
      default => NULL,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // A query using the generic emulation is potentially two queries, so it
    // has no string representation. Let the parent implementation throw.
    if (!$this->isNativeMerge()) {
      return parent::__toString();
    }

    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    $this->condition->compile($this->connection, $this);

    // MERGE requires a source relation even when merging a single row of
    // literal values, so use a constant single-row subquery. The ON clause
    // then only matches against the merge table.
    $query = $comments . 'MERGE INTO {' . $this->table . '} USING (SELECT 1) AS drupal_merge_source ON (' . $this->condition . ')';

    if ($this->needsUpdate) {
      $update_fields = [];
      // Expressions take priority over literal fields, so we process those
      // first and remove any literal fields that conflict.
      foreach ($this->expressionFields as $field => $data) {
        if ($data['expression'] instanceof SelectInterface) {
          $data['expression']->compile($this->connection, $this);
          $update_fields[] = $this->connection->escapeField($field) . ' = (' . $data['expression'] . ')';
        }
        else {
          $update_fields[] = $this->connection->escapeField($field) . ' = ' . $data['expression'];
        }
      }
      $max_placeholder = 0;
      foreach (array_diff_key($this->updateFields, $this->expressionFields) as $field => $value) {
        $update_fields[] = $this->connection->escapeField($field) . ' = :db_update_placeholder_' . ($max_placeholder++);
      }
      $query .= ' WHEN MATCHED THEN UPDATE SET ' . implode(', ', $update_fields);
    }

    $insert_fields = [];
    $values = [];
    $max_placeholder = 0;
    foreach (array_keys($this->insertFields) as $field) {
      $insert_fields[] = $this->connection->escapeField($field);
      $values[] = ':db_insert_placeholder_' . ($max_placeholder++);
    }
    foreach ($this->defaultFields as $field) {
      $insert_fields[] = $this->connection->escapeField($field);
      $values[] = 'DEFAULT';
    }
    $query .= ' WHEN NOT MATCHED THEN INSERT (' . implode(', ', $insert_fields) . ') VALUES (' . implode(', ', $values) . ')';

    // The merge_action() function reports whether the row was inserted or
    // updated, which execute() maps to the STATUS_INSERT and STATUS_UPDATE
    // return values.
    return $query . ' RETURNING merge_action()';
  }

}
