<?php

declare(strict_types=1);

namespace Drupal\mysql\Driver\Database\mysql;

use Drupal\Core\Database\Transaction\ClientConnectionTransactionState;
use Drupal\Core\Database\Transaction\TransactionManagerBase;

/**
 * MySql implementation of TransactionManagerInterface.
 *
 * MySQL will automatically commit transactions when tables are altered or
 * created (DDL transactions are not supported). However, pdo_mysql tracks
 * whether a client connection is still active and we can prevent triggering
 * exceptions.
 */
class TransactionManager extends TransactionManagerBase {

  /**
   * {@inheritdoc}
   */
  protected function beginClientTransaction(): bool {
    return $this->connection->getClientConnection()->beginTransaction();
  }

  /**
   * {@inheritdoc}
   */
  protected function processRootCommit(): void {
    if (!$this->connection->getClientConnection()->inTransaction()) {
      $this->voidClientTransaction();
      return;
    }
    parent::processRootCommit();
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientSavepoint(string $name): bool {
    if (!$this->connection->getClientConnection()->inTransaction()) {
      $this->voidClientTransaction();
      return TRUE;
    }
    return parent::rollbackClientSavepoint($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function releaseClientSavepoint(string $name): bool {
    if (!$this->connection->getClientConnection()->inTransaction()) {
      $this->voidClientTransaction();
      return TRUE;
    }
    return parent::releaseClientSavepoint($name);
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

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientTransaction(): bool {
    if (!$this->connection->getClientConnection()->inTransaction()) {
      trigger_error('Rollback attempted when there is no active transaction. This can cause data integrity issues.', E_USER_WARNING);
      $this->voidClientTransaction();
      return FALSE;
    }
    $clientRollback = $this->connection->getClientConnection()->rollBack();
    $this->setConnectionTransactionState($clientRollback ?
      ClientConnectionTransactionState::RolledBack :
      ClientConnectionTransactionState::RollbackFailed
    );
    return $clientRollback;
  }

}
