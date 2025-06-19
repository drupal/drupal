<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli;

use Drupal\Core\Database\Transaction\ClientConnectionTransactionState;
use Drupal\Core\Database\Transaction\TransactionManagerBase;

/**
 * MySqli implementation of TransactionManagerInterface.
 */
class TransactionManager extends TransactionManagerBase {

  /**
   * {@inheritdoc}
   */
  protected function beginClientTransaction(): bool {
    return $this->connection->getClientConnection()->begin_transaction();
  }

  /**
   * {@inheritdoc}
   */
  protected function addClientSavepoint(string $name): bool {
    return $this->connection->getClientConnection()->savepoint($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientSavepoint(string $name): bool {
    // Mysqli does not have a rollback_to_savepoint method, and it does not
    // allow a prepared statement for 'ROLLBACK TO SAVEPOINT', so we need to
    // fallback to querying on the client connection directly.
    try {
      return (bool) $this->connection->getClientConnection()->query('ROLLBACK TO SAVEPOINT ' . $name);
    }
    catch (\mysqli_sql_exception) {
      // If the rollback failed, most likely the savepoint was not there
      // because the transaction is no longer active. In this case we void the
      // transaction stack.
      $this->voidClientTransaction();
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function releaseClientSavepoint(string $name): bool {
    return $this->connection->getClientConnection()->release_savepoint($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientTransaction(): bool {
    // Note: mysqli::rollback() returns TRUE if there's no active transaction.
    // This is diverging from PDO MySql. A PHP bug report exists.
    // @see https://bugs.php.net/bug.php?id=81533.
    $clientRollback = $this->connection->getClientConnection()->rollBack();
    $this->setConnectionTransactionState($clientRollback ?
      ClientConnectionTransactionState::RolledBack :
      ClientConnectionTransactionState::RollbackFailed
    );
    return $clientRollback;
  }

  /**
   * {@inheritdoc}
   */
  protected function commitClientTransaction(): bool {
    $clientCommit = $this->connection->getClientConnection()->commit();
    $this->setConnectionTransactionState($clientCommit ?
      ClientConnectionTransactionState::Committed :
      ClientConnectionTransactionState::CommitFailed
    );
    return $clientCommit;
  }

}
