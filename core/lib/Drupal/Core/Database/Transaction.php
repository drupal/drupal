<?php

namespace Drupal\Core\Database;

/**
 * A wrapper class for creating and managing database transactions.
 *
 * To begin a transaction, simply start it. When the object goes out of scope
 * and is destroyed it will automatically commit.
 *
 * In the vast majority of cases, you should not instantiate this class
 * directly. Instead, call ->startTransaction(), from the appropriate connection
 * object.
 *
 * @see \Drupal\Core\Database\Connection::startTransaction()
 */
class Transaction {

  public function __construct(
    protected readonly Connection $connection,
    protected readonly string $name,
    protected readonly string $id,
  ) {
    // Transactions rely on objects being destroyed in order to be committed.
    // PHP makes no guarantee about the order in which objects are destroyed so
    // ensure all transactions are committed on shutdown.
    Database::commitAllOnShutdown();
  }

  /**
   * Destructs the object.
   *
   * Depending on the nesting level of the object, this leads to a COMMIT (for
   * a root item) or to a RELEASE SAVEPOINT (for a savepoint item) executed on
   * the database.
   */
  public function __destruct() {
    $this->connection->transactionManager()->unpile($this->name, $this->id);
  }

  /**
   * Retrieves the name of the transaction or savepoint.
   */
  public function name() {
    return $this->name;
  }

  /**
   * Rolls back the current transaction.
   *
   * This is just a wrapper method to rollback whatever transaction stack we are
   * currently in, which is managed by the TransactionManager. Note that logging
   * needs to happen after a transaction has been rolled back or the log
   * messages will be rolled back too.
   *
   * Depending on the nesting level of the object, this leads to a ROLLBACK (for
   * a root item) or to a ROLLBACK TO SAVEPOINT (for a savepoint item) executed
   * on the database.
   */
  public function rollBack() {
    $this->connection->transactionManager()->rollback($this->name, $this->id);
  }

}
