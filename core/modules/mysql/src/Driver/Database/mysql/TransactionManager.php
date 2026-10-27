<?php

declare(strict_types=1);

namespace Drupal\mysql\Driver\Database\mysql;

use Drupal\Core\Database\Transaction\TransactionManagerBase;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\TransactionException;
use Drupal\Core\Database\TransactionNoActiveException;

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
    try {
      return $this->connection->getClientConnection()->beginTransaction();
    }
    catch (\Exception $e) {
      throw new TransactionException('Failed start of transaction', $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientSavepoint(string $name): bool {
    if (!$this->connection->getClientConnection()->inTransaction()) {
      throw new TransactionNoActiveException();
    }
    return parent::rollbackClientSavepoint($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function releaseClientSavepoint(string $name): bool {
    if (!$this->connection->getClientConnection()->inTransaction()) {
      throw new TransactionNoActiveException();
    }
    return parent::releaseClientSavepoint($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function commitClientTransaction(): bool {
    try {
      return $this->connection->getClientConnection()->commit();
    }
    catch (\Exception $e) {
      throw new TransactionCommitFailedException('Failed commit of transaction', $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientTransaction(): bool {
    if (!$this->connection->getClientConnection()->inTransaction()) {
      throw new TransactionNoActiveException();
    }
    try {
      return $this->connection->getClientConnection()->rollBack();
    }
    catch (\Exception $e) {
      throw new TransactionException('Failed rollback of transaction', $e->getCode(), $e);
    }
  }

}
