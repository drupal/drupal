<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Database;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Transaction\TransactionManagerBase;
use Drupal\Core\Database\TransactionCommitFailedException;
use Drupal\Core\Database\TransactionException;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests transactions.
 */
#[CoversClass(TransactionManagerBase::class)]
#[Group('Database')]
class TransactionManagerTest extends UnitTestCase {

  /**
   * A database connection.
   */
  protected Connection $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->connection = new StubConnection($this->createStub(\PDO::class), []);
  }

  /**
   * Tests starting a transaction.
   */
  public function testStartTransaction(): void {
    $tx = $this->connection->startTransaction();
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());
    $this->assertTrue($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertTrue($this->connection->inTransaction());
    $tx->commitOrRelease();
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests failing to start a transaction.
   */
  public function testStartTransactionFailure(): void {
    $this->connection->transactionManager()->success = FALSE;
    $this->expectException(TransactionException::class);
    $this->expectExceptionMessage('Failed start of transaction');
    // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $tx = $this->connection->startTransaction();
  }

  /**
   * Checks TransactionManager after failing to start a transaction.
   */
  public function testStateAfterStartTransactionFailure(): void {
    try {
      $this->connection->transactionManager()->success = FALSE;
      // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
      $tx = $this->connection->startTransaction();
    }
    catch (TransactionException) {
      // Continue.
    }
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests starting a savepoint.
   */
  public function testStartSavepoint(): void {
    $tx = $this->connection->startTransaction();
    $savepoint = $this->connection->startTransaction('foo');
    $this->assertSame(2, $this->connection->transactionManager()->stackDepth());
    $this->assertTrue($this->connection->transactionManager()->has('foo'));
    $this->assertTrue($this->connection->inTransaction());
    $savepoint->commitOrRelease();
    $tx->commitOrRelease();
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('foo'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests failing to start a savepoint.
   */
  public function testStartSavepointFailure(): void {
    // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $tx = $this->connection->startTransaction();
    $this->connection->transactionManager()->success = FALSE;
    $this->expectException(TransactionException::class);
    $this->expectExceptionMessage('Failed start of savepoint foo');
    // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $savepoint = $this->connection->startTransaction('foo');
  }

  /**
   * Checks TransactionManager after failing to start a savepoint.
   */
  public function testStateAfterStartSavepointFailure(): void {
    // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $tx = $this->connection->startTransaction();
    try {
      $this->connection->transactionManager()->success = FALSE;
      // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
      $savepoint = $this->connection->startTransaction('foo');
    }
    catch (TransactionException) {
      // Continue.
    }
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests committing a transaction.
   */
  public function testTransactionCommit(): void {
    $tx = $this->connection->startTransaction();
    $tx->commitOrRelease();
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests a transaction commit failure.
   */
  public function testTransactionCommitFailure(): void {
    $tx = $this->connection->startTransaction();
    $this->connection->transactionManager()->success = FALSE;
    $this->expectException(TransactionCommitFailedException::class);
    $this->expectExceptionMessage('Failed commit of transaction');
    $tx->commitOrRelease();
  }

  /**
   * Checks TransactionManager after transaction commit failure.
   */
  public function testStateAfterTransactionCommitFailure(): void {
    try {
      $tx = $this->connection->startTransaction();
      $this->connection->transactionManager()->success = FALSE;
      $tx->commitOrRelease();
    }
    catch (TransactionCommitFailedException) {
      // Continue.
    }
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests releasing a savepoint.
   */
  public function testTransactionReleaseSavepoint(): void {
    $tx = $this->connection->startTransaction();
    $savepoint = $this->connection->startTransaction('foo');
    $savepoint->commitOrRelease();
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('foo'));
    $this->assertTrue($this->connection->inTransaction());
    $tx->commitOrRelease();
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests a transaction release savepoint failure.
   */
  public function testTransactionReleaseSavepointFailure(): void {
    // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $tx = $this->connection->startTransaction();
    $savepoint = $this->connection->startTransaction('foo');
    $this->connection->transactionManager()->success = FALSE;
    $this->expectException(TransactionCommitFailedException::class);
    $this->expectExceptionMessage('Failed release of savepoint foo');
    $savepoint->commitOrRelease();
  }

  /**
   * Checks TransactionManager after release savepoint failure.
   */
  public function testStateAfterTransactionReleaseSavepointFailure(): void {
    try {
      // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
      $tx = $this->connection->startTransaction();
      $savepoint = $this->connection->startTransaction('foo');
      $this->connection->transactionManager()->success = FALSE;
      $savepoint->commitOrRelease();
    }
    catch (TransactionCommitFailedException) {
      // Continue.
    }
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('foo'));
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests rolling back a transaction.
   */
  public function testTransactionRollback(): void {
    $tx = $this->connection->startTransaction();
    $tx->rollback();
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests a transaction rollback failure.
   */
  public function testTransactionRollbackFailure(): void {
    $tx = $this->connection->startTransaction();
    $this->connection->transactionManager()->success = FALSE;
    $this->expectException(TransactionException::class);
    $this->expectExceptionMessage('Failed rollback of transaction');
    $tx->rollback();
  }

  /**
   * Checks TransactionManager after transaction rollback failure.
   */
  public function testStateAfterTransactionRollbackFailure(): void {
    try {
      $tx = $this->connection->startTransaction();
      $this->connection->transactionManager()->success = FALSE;
      $tx->rollback();
    }
    catch (TransactionException) {
      // Continue.
    }
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests rolling back a transaction to a savepoint.
   */
  public function testTransactionRollbackToSavepoint(): void {
    $tx = $this->connection->startTransaction();
    $savepoint = $this->connection->startTransaction('foo');
    $savepoint->rollback();
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('foo'));
    $this->assertTrue($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertTrue($this->connection->inTransaction());
    $tx->commitOrRelease();
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('foo'));
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests failure when rolling back a transaction to a savepoint.
   */
  public function testTransactionRollbackToSavepointFailure(): void {
    // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
    $tx = $this->connection->startTransaction();
    $savepoint = $this->connection->startTransaction('foo');
    $this->connection->transactionManager()->success = FALSE;
    $this->expectException(TransactionException::class);
    $this->expectExceptionMessage('Failed rollback of savepoint foo');
    $savepoint->rollback();
  }

  /**
   * Checks TransactionManager after failure on rolling back to a savepoint.
   */
  public function testStateAfterTransactionRollbackToSavepointFailure(): void {
    try {
      // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
      $tx = $this->connection->startTransaction();
      $savepoint = $this->connection->startTransaction('foo');
      $this->connection->transactionManager()->success = FALSE;
      $savepoint->rollback();
    }
    catch (TransactionException) {
      // Continue.
    }
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->transactionManager()->has('foo'));
    $this->assertFalse($this->connection->transactionManager()->has('drupal_transaction'));
    $this->assertFalse($this->connection->inTransaction());
  }

}
