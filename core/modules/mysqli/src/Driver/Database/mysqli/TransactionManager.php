<?php

declare(strict_types=1);

namespace Drupal\mysqli\Driver\Database\mysqli;

use Drupal\Core\Database\Transaction\TransactionManagerBase;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\TransactionException;

/**
 * MySqli implementation of TransactionManagerInterface.
 */
class TransactionManager extends TransactionManagerBase {

  /**
   * {@inheritdoc}
   */
  protected function beginClientTransaction(): bool {
    try {
      return $this->connection->getClientConnection()->begin_transaction();
    }
    catch (\Exception $e) {
      throw new TransactionException('Failed start of transaction', $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addClientSavepoint(string $name): bool {
    try {
      return $this->connection->getClientConnection()->savepoint($name);
    }
    catch (\Exception $e) {
      throw new TransactionException('Failed start of savepoint ' . $name, $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientSavepoint(string $name): bool {
    try {
      // Mysqli does not have a rollback_to_savepoint method, and it does not
      // allow a prepared statement for 'ROLLBACK TO SAVEPOINT', so we need to
      // fallback to querying on the client connection directly.
      return (bool) $this->connection->getClientConnection()->query('ROLLBACK TO SAVEPOINT ' . $name);
    }
    catch (\Exception $e) {
      throw new TransactionException('Failed rollback of savepoint ' . $name, $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function releaseClientSavepoint(string $name): bool {
    try {
      return $this->connection->getClientConnection()->release_savepoint($name);
    }
    catch (\Exception $e) {
      throw new TransactionCommitFailedException('Failed release of savepoint ' . $name, $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientTransaction(): bool {
    try {
      // Note: mysqli::rollback() returns TRUE if there's no active
      // transaction. This is diverging from PDO MySql. A PHP bug report
      // exists.
      // @see https://bugs.php.net/bug.php?id=81533.
      return $this->connection->getClientConnection()->rollBack();
    }
    catch (\Exception $e) {
      throw new TransactionException('Failed rollback of transaction', $e->getCode(), $e);
    }
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

}
