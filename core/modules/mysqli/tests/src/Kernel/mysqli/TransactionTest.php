<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Kernel\mysqli;

use Drupal\KernelTests\Core\Database\DriverSpecificTransactionTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests transaction for the MySQLi driver.
 */
#[Group('Database')]
class TransactionTest extends DriverSpecificTransactionTestBase {

  /**
   * Tests starting a transaction when there's one active on the client.
   *
   * MySQLi does not fail if multiple commits are made on the client, so this
   * test is failing. Let's change this if/when MySQLi will provide a way to
   * check if a client transaction is active.
   *
   * This is mitigated by the fact that transaction should not be initiated from
   * code outside the TransactionManager, that keeps track of the stack of
   * transaction-related operations in its stack.
   */
  public function testStartTransactionWhenActive(): void {
    $this->markTestSkipped('Skipping this while MySQLi cannot detect if a client transaction is active.');
    $this->connection->getClientConnection()->begin_transaction();
    $this->connection->startTransaction();
    $this->assertFalse($this->connection->inTransaction());
  }

  /**
   * Tests committing a transaction when there's none active on the client.
   *
   * MySQLi does not fail if multiple commits are made on the client, so this
   * test is failing. Let's change this if/when MySQLi will provide a way to
   * check if a client transaction is active.
   *
   * This is mitigated by the fact that transaction should not be initiated from
   * code outside the TransactionManager, that keeps track of the stack of
   * transaction-related operations in its stack.
   */
  public function testCommitTransactionWhenInactive(): void {
    $this->markTestSkipped('Skipping this while MySQLi cannot detect if a client transaction is active.');
    $transaction = $this->connection->startTransaction();
    $this->assertTrue($this->connection->inTransaction());
    $this->connection->getClientConnection()->commit();
    $this->assertFalse($this->connection->inTransaction());
    unset($transaction);
  }

}
