<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Transaction;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Transaction;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\Database\TransactionNoActiveException;
use Drupal\Core\Database\TransactionOutOfOrderException;

/**
 * The database transaction manager base class.
 *
 * On many databases transactions cannot nest. Instead, we track nested calls
 * to transactions and collapse them into a single client transaction.
 *
 * Database drivers must implement their own class extending from this, and
 * instantiate it via their Connection::driverTransactionManager() method.
 *
 * @see \Drupal\Core\Database\Connection::driverTransactionManager()
 */
abstract class TransactionManagerBase implements TransactionManagerInterface {

  /**
   * The stack of Drupal transactions currently active.
   *
   * This is not a real LIFO (Last In, First Out) stack, where we would only
   * remove the layers according to the order they were introduced. For commits
   * the layer order is enforced, while for rollbacks the API allows to
   * rollback to savepoints before the last one.
   *
   * @var array<string,StackItemType>
   */
  private array $stack = [];

  /**
   * A list of Drupal transactions rolled back but not yet unpiled.
   *
   * @var array<string,true>
   */
  private array $rollbacks = [];

  /**
   * A list of post-transaction callbacks.
   *
   * @var callable[]
   */
  private array $postTransactionCallbacks = [];

  /**
   * The state of the underlying client connection transaction.
   *
   * Note that this is a proxy of the actual state on the database server,
   * best determined through calls to methods in this class. The actual
   * state on the database server could be different.
   */
  private ClientConnectionTransactionState $connectionTransactionState;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    protected readonly Connection $connection,
  ) {
  }

  /**
   * Returns the current depth of the transaction stack.
   *
   * @return int
   *   The current depth of the transaction stack.
   *
   * @todo consider making this function protected.
   *
   * @internal
   */
  public function stackDepth(): int {
    return count($this->stack());
  }

  /**
   * Returns the content of the transaction stack.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   *
   * phpcs:ignore Drupal.Commenting.FunctionComment.InvalidReturn
   * @return array<string,StackItemType>
   *   The elements of the transaction stack.
   */
  protected function stack(): array {
    return $this->stack;
  }

  /**
   * Resets the transaction stack.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   */
  protected function resetStack(): void {
    $this->stack = [];
  }

  /**
   * Adds an item to the transaction stack.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   *
   * @param string $name
   *   The name of the transaction.
   * @param \Drupal\Core\Database\Transaction\StackItemType $type
   *   The stack item type.
   */
  protected function addStackItem(string $name, StackItemType $type): void {
    $this->stack[$name] = $type;
  }

  /**
   * Removes an item from the transaction stack.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   *
   * @param string $name
   *   The name of the transaction.
   */
  protected function removeStackItem(string $name): void {
    unset($this->stack[$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function inTransaction(): bool {
    return (bool) $this->stackDepth() && $this->getConnectionTransactionState() === ClientConnectionTransactionState::Active;
  }

  /**
   * {@inheritdoc}
   */
  public function push(string $name = ''): Transaction {
    if (!$this->inTransaction()) {
      // If there is no transaction active, name the transaction
      // 'drupal_transaction'.
      $name = 'drupal_transaction';
    }
    elseif (!$name) {
      // Within transactions, savepoints are used. Each savepoint requires a
      // name. So if no name is present we need to create one.
      $name = 'savepoint_' . $this->stackDepth();
    }

    if ($this->has($name)) {
      throw new TransactionNameNonUniqueException($name . " is already in use.");
    }

    // Do the client-level processing.
    if ($this->stackDepth() === 0) {
      $this->beginClientTransaction();
      $type = StackItemType::Root;
      $this->setConnectionTransactionState(ClientConnectionTransactionState::Active);
    }
    else {
      // If we're already in a Drupal transaction then we want to create a
      // database savepoint, rather than try to begin another database
      // transaction.
      $this->addClientSavepoint($name);
      $type = StackItemType::Savepoint;
    }

    // Push the transaction on the stack, increasing its depth.
    $this->addStackItem($name, $type);

    return new Transaction($this->connection, $name);
  }

  /**
   * {@inheritdoc}
   */
  public function unpile(string $name): void {
    // If an already rolled back Drupal transaction, do nothing on the client
    // connection, just cleanup the list of transactions rolled back.
    if (isset($this->rollbacks[$name])) {
      unset($this->rollbacks[$name]);
      return;
    }

    if ($name !== 'drupal_transaction' && !$this->has($name)) {
      throw new TransactionOutOfOrderException();
    }

    // Release the client transaction savepoint in case the Drupal transaction
    // is not a root one.
    if (
      $this->has($name)
      && $this->stack()[$name] === StackItemType::Savepoint
      && $this->getConnectionTransactionState() === ClientConnectionTransactionState::Active
    ) {
      $this->releaseClientSavepoint($name);
    }

    // Remove the transaction from the stack.
    $this->removeStackItem($name);

    // If this was the last Drupal transaction open, we can commit the client
    // transaction.
    if (
      $this->stackDepth() === 0
      && $this->getConnectionTransactionState() === ClientConnectionTransactionState::Active
    ) {
      $this->processRootCommit();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(string $name): void {
    if (!$this->inTransaction()) {
      throw new TransactionNoActiveException();
    }

    // Do the client-level processing.
    match ($this->stack()[$name]) {
      StackItemType::Root => $this->processRootRollback(),
      StackItemType::Savepoint => $this->rollbackClientSavepoint($name),
    };

    // Rolled back item should match the last one in stack.
    if ($name !== array_key_last($this->stack())) {
      throw new TransactionOutOfOrderException();
    }

    $this->rollbacks[$name] = TRUE;
    $this->removeStackItem($name);

    // If this was the last Drupal transaction open, we can commit the client
    // transaction.
    if ($this->stackDepth() === 0 && $this->getConnectionTransactionState() === ClientConnectionTransactionState::Active) {
      $this->processRootCommit();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addPostTransactionCallback(callable $callback): void {
    if (!$this->inTransaction()) {
      throw new \LogicException('Root transaction end callbacks can only be added when there is an active transaction.');
    }
    $this->postTransactionCallbacks[] = $callback;
  }

  /**
   * {@inheritdoc}
   */
  public function has(string $name): bool {
    return isset($this->stack()[$name]);
  }

  /**
   * Sets the state of the client connection transaction.
   *
   * Note that this is a proxy of the actual state on the database server,
   * best determined through calls to methods in this class. The actual
   * state on the database server could be different.
   *
   * Drivers should not override this method unless they also override the
   * $connectionTransactionState property.
   *
   * @param \Drupal\Core\Database\Transaction\ClientConnectionTransactionState $state
   *   The state of the client connection.
   */
  protected function setConnectionTransactionState(ClientConnectionTransactionState $state): void {
    $this->connectionTransactionState = $state;
  }

  /**
   * Gets the state of the client connection transaction.
   *
   * Note that this is a proxy of the actual state on the database server,
   * best determined through calls to methods in this class. The actual
   * state on the database server could be different.
   *
   * Drivers should not override this method unless they also override the
   * $connectionTransactionState property.
   *
   * @return \Drupal\Core\Database\Transaction\ClientConnectionTransactionState
   *   The state of the client connection.
   */
  protected function getConnectionTransactionState(): ClientConnectionTransactionState {
    return $this->connectionTransactionState;
  }

  /**
   * Processes the root transaction rollback.
   */
  protected function processRootRollback(): void {
    $this->processPostTransactionCallbacks();
    $this->rollbackClientTransaction();
  }

  /**
   * Processes the root transaction commit.
   *
   * @throws \Drupal\Core\Database\TransactionCommitFailedException
   *   If the commit of the root transaction failed.
   */
  protected function processRootCommit(): void {
    $clientCommit = $this->commitClientTransaction();
    $this->processPostTransactionCallbacks();
    if (!$clientCommit) {
      throw new TransactionCommitFailedException();
    }
  }

  /**
   * Processes the post-transaction callbacks.
   */
  protected function processPostTransactionCallbacks(): void {
    if (!empty($this->postTransactionCallbacks)) {
      $callbacks = $this->postTransactionCallbacks;
      $this->postTransactionCallbacks = [];
      foreach ($callbacks as $callback) {
        call_user_func($callback, $this->getConnectionTransactionState() === ClientConnectionTransactionState::Committed || $this->getConnectionTransactionState() === ClientConnectionTransactionState::Voided);
      }
    }
  }

  /**
   * Begins a transaction on the client connection.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  abstract protected function beginClientTransaction(): bool;

  /**
   * Adds a savepoint on the client transaction.
   *
   * This is a generic implementation. Drivers should override this method
   * to use a method specific for their client connection.
   *
   * @param string $name
   *   The name of the savepoint.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  protected function addClientSavepoint(string $name): bool {
    $this->connection->query('SAVEPOINT ' . $name);
    return TRUE;
  }

  /**
   * Rolls back to a savepoint on the client transaction.
   *
   * This is a generic implementation. Drivers should override this method
   * to use a method specific for their client connection.
   *
   * @param string $name
   *   The name of the savepoint.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  protected function rollbackClientSavepoint(string $name): bool {
    $this->connection->query('ROLLBACK TO SAVEPOINT ' . $name);
    return TRUE;
  }

  /**
   * Releases a savepoint on the client transaction.
   *
   * This is a generic implementation. Drivers should override this method
   * to use a method specific for their client connection.
   *
   * @param string $name
   *   The name of the savepoint.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  protected function releaseClientSavepoint(string $name): bool {
    $this->connection->query('RELEASE SAVEPOINT ' . $name);
    return TRUE;
  }

  /**
   * Rolls back a client transaction.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  abstract protected function rollbackClientTransaction(): bool;

  /**
   * Commits a client transaction.
   *
   * @return bool
   *   Returns TRUE on success or FALSE on failure.
   */
  abstract protected function commitClientTransaction(): bool;

}
