<?php

declare(strict_types=1);

namespace Drupal\Core\Database\Transaction;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Transaction;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\TransactionNameNonUniqueException;
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
   * This property is keeping track of the Transaction objects started and
   * ended as a LIFO (Last In, First Out) stack.
   *
   * The database API allows to begin transactions, add an arbitrary number of
   * additional savepoints, and release any savepoint in the sequence. When
   * this happens, the database will implicitly release all the savepoints
   * created after the one released. Given Drupal implementation of the
   * Transaction objects, we cannot force descoping of the corresponding
   * Transaction savepoint objects from the manager, because they live in the
   * scope of the calling code. This stack ensures that when an outlived
   * Transaction object gets out of scope, it will not try to release on the
   * database a savepoint that no longer exists.
   *
   * Differently, rollbacks are strictly being checked for LIFO order: if a
   * rollback is requested against a savepoint that is not the last created,
   * the manager will throw a TransactionOutOfOrderException.
   *
   * The array key is the transaction's unique id, its value a StackItem.
   *
   * @var array<string,StackItem>
   */
  private array $stack = [];

  /**
   * A list of voided stack items.
   *
   * In some cases the active transaction can be automatically committed by the
   * database server (for example, MySql when a DDL statement is executed
   * during a transaction). In such cases we need to void the remaining items
   * on the stack, and we track them here.
   *
   * The array key is the transaction's unique id, its value a StackItem.
   *
   * @var array<string,StackItem>
   */
  private array $voidedItems = [];

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
   * Destructor.
   *
   * When destructing, $stack must have been already emptied.
   */
  public function __destruct() {
    assert($this->stack === [], "Transaction \$stack was not empty. Active stack: " . $this->dumpStackItemsAsString());
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
   * @return array<string,StackItem>
   *   The elements of the transaction stack.
   */
  protected function stack(): array {
    return $this->stack;
  }

  /**
   * Commits the entire transaction stack.
   *
   * @internal
   *   This method exists only to work around a bug caused by Drupal incorrectly
   *   relying on object destruction order to commit transactions. Xdebug 3.3.0
   *   changes the order of object destruction when the develop mode is enabled.
   */
  public function commitAll(): void {
    foreach (array_reverse($this->stack()) as $id => $item) {
      $this->unpile($item->name, $id);
    }
  }

  /**
   * Adds an item to the transaction stack.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   *
   * @param string $id
   *   The id of the transaction.
   * @param \Drupal\Core\Database\Transaction\StackItem $item
   *   The stack item.
   */
  protected function addStackItem(string $id, StackItem $item): void {
    $this->stack[$id] = $item;
  }

  /**
   * Removes an item from the transaction stack.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   *
   * @param string $id
   *   The id of the transaction.
   */
  protected function removeStackItem(string $id): void {
    unset($this->stack[$id]);
  }

  /**
   * Voids an item from the transaction stack.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   *
   * @param string $id
   *   The id of the transaction.
   */
  protected function voidStackItem(string $id): void {
    // The item should be removed from $stack and added to $voidedItems for
    // later processing.
    $this->voidedItems[$id] = $this->stack[$id];
    $this->removeStackItem($id);
  }

  /**
   * Produces a string representation of the stack items.
   *
   * A helper method for exception messages.
   *
   * Drivers should not override this method unless they also override the
   * $stack property.
   *
   * @return string
   *   The string representation of the stack items.
   */
  protected function dumpStackItemsAsString(): string {
    if ($this->stack() === []) {
      return '*** empty ***';
    }

    $temp = [];
    foreach ($this->stack() as $id => $item) {
      $temp[] = $id . '\\' . $item->name;
    }
    return implode(' > ', $temp);
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
      throw new TransactionNameNonUniqueException("A transaction named {$name} is already in use. Active stack: " . $this->dumpStackItemsAsString());
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

    // Define an unique id for the transaction.
    $id = uniqid('', TRUE);

    // Add an item on the stack, increasing its depth.
    $this->addStackItem($id, new StackItem($name, $type));

    // Actually return a new Transaction object.
    return new Transaction($this->connection, $name, $id);
  }

  /**
   * {@inheritdoc}
   */
  public function unpile(string $name, string $id): void {
    // If the $id does not correspond to the one in the stack for that $name,
    // we are facing an orphaned Transaction object (for example in case of a
    // DDL statement breaking an active transaction). That should be listed in
    // $voidedItems, so we can remove it from there.
    if (!isset($this->stack()[$id]) || $this->stack()[$id]->name !== $name) {
      unset($this->voidedItems[$id]);
      return;
    }

    // If we are not releasing the last savepoint but an earlier one, or
    // committing a root transaction while savepoints are active, all
    // subsequent savepoints will be released as well. The stack must be
    // diminished accordingly.
    while (($i = array_key_last($this->stack())) != $id) {
      $this->voidStackItem((string) $i);
    }

    if ($this->getConnectionTransactionState() === ClientConnectionTransactionState::Active) {
      if ($this->stackDepth() > 1 && $this->stack()[$id]->type === StackItemType::Savepoint) {
        // Release the client transaction savepoint in case the Drupal
        // transaction is not a root one.
        $this->releaseClientSavepoint($name);
      }
      elseif ($this->stackDepth() === 1 && $this->stack()[$id]->type === StackItemType::Root) {
        // If this was the root Drupal transaction, we can commit the client
        // transaction.
        $this->processRootCommit();
      }
      else {
        // The stack got corrupted.
        throw new TransactionOutOfOrderException("Transaction {$id}/{$name} is out of order. Active stack: " . $this->dumpStackItemsAsString());
      }

      // Remove the transaction from the stack.
      $this->removeStackItem($id);
      return;
    }

    // The stack got corrupted.
    throw new TransactionOutOfOrderException("Transaction {$id}/{$name} is out of order. Active stack: " . $this->dumpStackItemsAsString());
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(string $name, string $id): void {
    // @todo remove in drupal:11.0.0.
    // Start of BC layer.
    if ($id === 'bc-force-rollback') {
      foreach ($this->stack() as $stackId => $item) {
        if ($item->name === $name) {
          $id = $stackId;
          break;
        }
      }
      if ($id === 'bc-force-rollback') {
        throw new TransactionOutOfOrderException();
      }
    }
    // End of BC layer.

    // Rolled back item should match the last one in stack.
    if ($id != array_key_last($this->stack()) || $name !== $this->stack()[$id]->name) {
      throw new TransactionOutOfOrderException("Error attempting rollback of {$id}\\{$name}. Active stack: " . $this->dumpStackItemsAsString());
    }

    if ($this->getConnectionTransactionState() === ClientConnectionTransactionState::Active) {
      if ($this->stackDepth() > 1 && $this->stack()[$id]->type === StackItemType::Savepoint) {
        // Rollback the client transaction to the savepoint when the Drupal
        // transaction is not a root one. Then, release the savepoint too. The
        // client connection remains active.
        $this->rollbackClientSavepoint($name);
        $this->releaseClientSavepoint($name);
        // The Transaction object remains open, and when it will get destructed
        // no commit should happen. Void the stack item.
        $this->voidStackItem($id);
      }
      elseif ($this->stackDepth() === 1 && $this->stack()[$id]->type === StackItemType::Root) {
        // If this was the root Drupal transaction, we can rollback the client
        // transaction. The transaction is closed.
        $this->processRootRollback();
        // The Transaction object remains open, and when it will get destructed
        // no commit should happen. Void the stack item.
        $this->voidStackItem($id);
      }
      else {
        // The stack got corrupted.
        throw new TransactionOutOfOrderException("Error attempting rollback of {$id}\\{$name}. Active stack: " . $this->dumpStackItemsAsString());
      }
      return;
    }

    // The stack got corrupted.
    throw new TransactionOutOfOrderException("Error attempting rollback of {$id}\\{$name}. Active stack: " . $this->dumpStackItemsAsString());
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
    foreach ($this->stack() as $item) {
      if ($item->name === $name) {
        return TRUE;
      }
    }
    return FALSE;
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

  /**
   * {@inheritdoc}
   */
  public function voidClientTransaction(): void {
    while ($i = array_key_last($this->stack())) {
      $this->voidStackItem((string) $i);
    }
    $this->setConnectionTransactionState(ClientConnectionTransactionState::Voided);
    $this->processPostTransactionCallbacks();
  }

}
