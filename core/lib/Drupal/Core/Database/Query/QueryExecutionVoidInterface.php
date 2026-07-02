<?php

namespace Drupal\Core\Database\Query;

/**
 * Interface for queries whose execution does not return a value.
 *
 * This is typical of DDL and DML SQL statements.
 */
interface QueryExecutionVoidInterface {

  /**
   * Runs the query against the database.
   */
  public function execute(): void;

}
