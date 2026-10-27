<?php

declare(strict_types=1);

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\Transaction\TransactionManagerBase;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\TransactionException;

/**
 * SQLite implementation of TransactionManagerInterface.
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
  protected function rollbackClientTransaction(): bool {
    try {
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
