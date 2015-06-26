<?php

/**
 * @file
 * Contains \Drupal\Core\Database\Transaction.
 */

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

  /**
   * The connection object for this transaction.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * A boolean value to indicate whether this transaction has been rolled back.
   *
   * @var bool
   */
  protected $rolledBack = FALSE;

  /**
   * The name of the transaction.
   *
   * This is used to label the transaction savepoint. It will be overridden to
   * 'drupal_transaction' if there is no transaction depth.
   */
  protected $name;

  public function __construct(Connection $connection, $name = NULL) {
    $this->connection = $connection;
    // If there is no transaction depth, then no transaction has started. Name
    // the transaction 'drupal_transaction'.
    if (!$depth = $connection->transactionDepth()) {
      $this->name = 'drupal_transaction';
    }
    // Within transactions, savepoints are used. Each savepoint requires a
    // name. So if no name is present we need to create one.
    elseif (!$name) {
      $this->name = 'savepoint_' . $depth;
    }
    else {
      $this->name = $name;
    }
    $this->connection->pushTransaction($this->name);
  }

  public function __destruct() {
    // If we rolled back then the transaction would have already been popped.
    if (!$this->rolledBack) {
      $this->connection->popTransaction($this->name);
    }
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
   * logging (preferable with watchdog_exception()) needs to happen after a
   * transaction has been rolled back or the log messages will be rolled back
   * too.
   *
   * @see \Drupal\Core\Database\Connection::rollback()
   * @see watchdog_exception()
   */
  public function rollback() {
    $this->rolledBack = TRUE;
    $this->connection->rollback($this->name);
  }
}
