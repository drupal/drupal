<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Transaction;

use Drupal\Core\Database\Transaction;

/**
 * Interface for the database transaction manager classes.
 */
interface TransactionManagerInterface {

  /**
   * Determines if there is an active transaction open.
   *
   * @return bool
   *   TRUE if we're currently in a transaction, FALSE otherwise.
   */
  public function inTransaction(): bool;

  /**
   * Checks if a named Drupal transaction is active.
   *
   * @param string $name
   *   The name of the transaction.
   *
   * @return bool
   *   TRUE if the transaction is active, FALSE otherwise.
   */
  public function has(string $name): bool;

  /**
   * Pushes a new Drupal transaction on the stack.
   *
   * This begins a client connection transaction if there is not one active,
   * or adds a savepoint to the active one.
   *
   * This method should only be called internally by a database driver.
   *
   * @param string $name
   *   (optional) The name of the savepoint.
   *
   * @return \Drupal\Core\Database\Transaction
   *   A Transaction object.
   *
   * @throws \Drupal\Core\Database\TransactionNameNonUniqueException
   *   If a Drupal Transaction with the specified name exists already.
   */
  public function push(string $name = ''): Transaction;

  /**
   * Removes a Drupal transaction from the stack.
   *
   * The unpiled item does not necessarily need to be the last on the stack.
   * This method should only be called by a Transaction object's
   * ::commitOrRelease() method.
   *
   * This method should only be called internally by a database driver.
   *
   * @param string $name
   *   The name of the transaction.
   * @param string $id
   *   The id of the transaction.
   *
   * @throws \Drupal\Core\Database\TransactionOutOfOrderException
   *   If a Drupal Transaction with the specified name does not exist.
   * @throws \Drupal\Core\Database\TransactionCommitFailedException
   *   If the commit of the root transaction failed.
   *
   * @see \Drupal\Core\Database\Transaction::__destruct()
   */
  public function unpile(string $name, string $id): void;

  /**
   * Rolls back a Drupal transaction.
   *
   * Rollbacks for nested transactions need to occur in reverse order to the
   * pushes to the stack. Rolling back the last active Drupal transaction leads
   * to rolling back the client connection (or to committing it in the edge
   * case when the root was unpiled earlier).
   *
   * This method should only be called internally by a database driver.
   *
   * @param string $name
   *   The name of the transaction.
   * @param string $id
   *   The id of the transaction.
   *
   * @throws \Drupal\Core\Database\TransactionNoActiveException
   *   If there is no active client connection.
   * @throws \Drupal\Core\Database\TransactionOutOfOrderException
   *   If the order of rollback is not in reverse sequence against the pushes
   *   to the stack.
   * @throws \Drupal\Core\Database\TransactionCommitFailedException
   *   If the commit of the root transaction failed.
   *
   * @see \Drupal\Core\Database\Transaction::rollback()
   */
  public function rollback(string $name, string $id): void;

  /**
   * Voids the client connection.
   *
   * In some cases the active transaction can be automatically committed by the
   * database server (for example, MySql when a DDL statement is executed
   * during a transaction). In such cases we need to void the remaining items
   * on the stack so that when outliving Transaction object get out of scope
   * they will not try operations on the database.
   *
   * This method should only be called internally by a database driver.
   */
  public function voidClientTransaction(): void;

  /**
   * Adds a root transaction end callback.
   *
   * It can for example be used to avoid deadlocks on write-heavy tables that
   * do not need to be part of the transaction, like cache tag invalidations.
   *
   * Another use case is that services using alternative backends like Redis
   * and Memcache cache implementations can replicate the transaction-behavior
   * of the database cache backend and avoid race conditions.
   *
   * These callbacks are invoked during the destruction of the root Transaction
   * object.
   *
   * The callback should have the following signature:
   * @code
   *   callback(
   *     bool $success,
   *   ): void
   * @endcode
   *
   * When callbacks are executed, the $success parameter passed to the callbacks
   * is a boolean that indicates
   *   - if TRUE, that the complete transaction was successfully committed, or
   *     in the edge case of a transaction that was auto-committed after a DDL
   *     statement, that no rollbacks were attempted after the DDL statement;
   *   - if FALSE, that the complete transaction was rolled back, or that the
   *     transaction processing failed for any other reason.
   *
   * @param callable $callback
   *   The callback to invoke.
   *
   * @throws \LogicException
   *   When a callback addition is attempted but no transaction is active.
   */
  public function addPostTransactionCallback(callable $callback): void;

}
