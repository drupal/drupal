<?php

namespace Drupal\Core\Database;

/**
 * A wrapper class for creating and managing database transactions.
 *
 * Not all databases or database configurations support transactions. For
 * example, MySQL MyISAM tables do not. It is also easy to begin a transaction
 * and then forget to commit it, which can lead to connection errors when
 * another transaction is started.
 *
 * This class acts as a wrapper for transactions. To begin a transaction,
 * simply instantiate it. When the object goes out of scope and is destroyed
 * it will automatically commit. It also will check to see if the specified
 * connection supports transactions. If not, it will simply skip any transaction
 * commands, allowing user-space code to proceed normally. The only difference
 * is that rollbacks won't actually do anything.
 *
 * In the vast majority of cases, you should not instantiate this class
 * directly. Instead, call ->startTransaction(), from the appropriate connection
 * object.
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
   * currently in, which is managed by the connection object itself. Note that
   * logging needs to happen after a transaction has been rolled back or the log
   * messages will be rolled back too.
   *
   * @see \Drupal\Core\Database\Connection::rollBack()
   */
  public function rollBack() {
    $this->connection->transactionManager()->rollback($this->name, $this->id);
  }

}
