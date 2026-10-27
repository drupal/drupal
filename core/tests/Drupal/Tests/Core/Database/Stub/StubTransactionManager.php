<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database\Stub;

use Drupal\Core\Database\Transaction\TransactionManagerBase;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\TransactionException;

/**
 * A stub of the TransactionManagerBase class for testing purposes.
 */
class StubTransactionManager extends TransactionManagerBase {

  /**
   * Should methods succeed or not.
   */
  public bool $success = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function beginClientTransaction(): bool {
    return $this->success ? TRUE : throw new TransactionException('Failed start of transaction');
  }

  /**
   * {@inheritdoc}
   */
  protected function addClientSavepoint(string $name): bool {
    return $this->success ? TRUE : throw new TransactionException('Failed start of savepoint ' . $name);
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientSavepoint(string $name): bool {
    return $this->success ? TRUE : throw new TransactionException('Failed rollback of savepoint ' . $name);
  }

  /**
   * {@inheritdoc}
   */
  protected function releaseClientSavepoint(string $name): bool {
    return $this->success ? TRUE : throw new TransactionCommitFailedException('Failed release of savepoint ' . $name);
  }

  /**
   * {@inheritdoc}
   */
  protected function commitClientTransaction(): bool {
    return $this->success ? TRUE : throw new TransactionCommitFailedException('Failed commit of transaction');
  }

  /**
   * {@inheritdoc}
   */
  protected function rollbackClientTransaction(): bool {
    return $this->success ? TRUE : throw new TransactionException('Failed rollback of transaction');
  }

}
