<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\TransactionExplicitCommitNotAllowedException;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\Database\TransactionNoActiveException;
use Drupal\Core\Database\TransactionOutOfOrderException;
use PHPUnit\Framework\Error\Warning;

/**
 * Tests the transaction abstraction system.
 *
 * We test nesting by having two transaction layers, an outer and inner. The
 * outer layer encapsulates the inner layer. Our transaction nesting abstraction
 * should allow the outer layer function to call any function it wants,
 * especially the inner layer that starts its own transaction, and be
 * confident that, when the function it calls returns, its own transaction
 * is still "alive."
 *
 * Call structure:
 *   transactionOuterLayer()
 *     Start transaction
 *     transactionInnerLayer()
 *       Start transaction (does nothing in database)
 *       [Maybe decide to roll back]
 *     Do more stuff
 *     Should still be in transaction A
 *
 * These method can be overridden by non-core database driver if their
 * transaction behavior is different from core. For example, both oci8 (Oracle)
 * and mysqli (MySql) clients do not have a solution to check if a transaction
 * is active, and mysqli does not fail when rolling back and no transaction
 * active.
 */
class DriverSpecificTransactionTestBase extends DriverSpecificDatabaseTestBase {

  /**
   * Keeps track of the post-transaction callback action executed.
   */
  protected ?string $postTransactionCallbackAction = NULL;

  /**
   * Encapsulates a transaction's "inner layer" with an "outer layer".
   *
   * This "outer layer" transaction starts and then encapsulates the "inner
   * layer" transaction. This nesting is used to evaluate whether the database
   * transaction API properly supports nesting. By "properly supports," we mean
   * the outer transaction continues to exist regardless of what functions are
   * called and whether those functions start their own transactions.
   *
   * In contrast, a typical database would commit the outer transaction, start
   * a new transaction for the inner layer, commit the inner layer transaction,
   * and then be confused when the outer layer transaction tries to commit its
   * transaction (which was already committed when the inner transaction
   * started).
   *
   * @param $suffix
   *   Suffix to add to field values to differentiate tests.
   * @param $rollback
   *   Whether or not to try rolling back the transaction when we're done.
   * @param $ddl_statement
   *   Whether to execute a DDL statement during the inner transaction.
   */
  protected function transactionOuterLayer($suffix, $rollback = FALSE, $ddl_statement = FALSE) {
    $depth = $this->connection->transactionManager()->stackDepth();
    $txn = $this->connection->startTransaction();

    // Insert a single row into the testing table.
    $this->connection->insert('test')
      ->fields([
        'name' => 'David' . $suffix,
        'age' => '24',
      ])
      ->execute();

    $this->assertTrue($this->connection->inTransaction(), 'In transaction before calling nested transaction.');

    // We're already in a transaction, but we call ->transactionInnerLayer
    // to nest another transaction inside the current one.
    $this->transactionInnerLayer($suffix, $rollback, $ddl_statement);

    $this->assertTrue($this->connection->inTransaction(), 'In transaction after calling nested transaction.');

    if ($rollback) {
      // Roll back the transaction, if requested.
      // This rollback should propagate to the last savepoint.
      $txn->rollBack();
      $this->assertSame($depth, $this->connection->transactionManager()->stackDepth(), 'Transaction has rolled back to the last savepoint after calling rollBack().');
    }
  }

  /**
   * Creates an "inner layer" transaction.
   *
   * This "inner layer" transaction is either used alone or nested inside of the
   * "outer layer" transaction.
   *
   * @param $suffix
   *   Suffix to add to field values to differentiate tests.
   * @param $rollback
   *   Whether or not to try rolling back the transaction when we're done.
   * @param $ddl_statement
   *   Whether to execute a DDL statement during the transaction.
   */
  protected function transactionInnerLayer($suffix, $rollback = FALSE, $ddl_statement = FALSE) {
    $depth = $this->connection->transactionManager()->stackDepth();
    // Start a transaction. If we're being called from ->transactionOuterLayer,
    // then we're already in a transaction. Normally, that would make starting
    // a transaction here dangerous, but the database API handles this problem
    // for us by tracking the nesting and avoiding the danger.
    $txn = $this->connection->startTransaction();

    $depth2 = $this->connection->transactionManager()->stackDepth();
    $this->assertGreaterThan($depth, $depth2, 'Transaction depth has increased with new transaction.');

    // Insert a single row into the testing table.
    $this->connection->insert('test')
      ->fields([
        'name' => 'Daniel' . $suffix,
        'age' => '19',
      ])
      ->execute();

    $this->assertTrue($this->connection->inTransaction(), 'In transaction inside nested transaction.');

    if ($ddl_statement) {
      $table = [
        'fields' => [
          'id' => [
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['id'],
      ];
      $this->connection->schema()->createTable('database_test_1', $table);

      $this->assertTrue($this->connection->inTransaction(), 'In transaction inside nested transaction.');
    }

    if ($rollback) {
      // Roll back the transaction, if requested.
      // This rollback should propagate to the last savepoint.
      $txn->rollBack();
      $this->assertSame($depth, $this->connection->transactionManager()->stackDepth(), 'Transaction has rolled back to the last savepoint after calling rollBack().');
    }
  }

  /**
   * Tests transaction rollback on a database that supports transactions.
   *
   * If the active connection does not support transactions, this test does
   * nothing.
   */
  public function testTransactionRollBackSupported() {
    try {
      // Create two nested transactions. Roll back from the inner one.
      $this->transactionOuterLayer('B', TRUE);

      // Neither of the rows we inserted in the two transaction layers
      // should be present in the tables post-rollback.
      $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'DavidB'])->fetchField();
      $this->assertNotSame('24', $saved_age, 'Cannot retrieve DavidB row after commit.');
      $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'DanielB'])->fetchField();
      $this->assertNotSame('19', $saved_age, 'Cannot retrieve DanielB row after commit.');
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  /**
   * Tests a committed transaction.
   *
   * The behavior of this test should be identical for connections that support
   * transactions and those that do not.
   */
  public function testCommittedTransaction() {
    try {
      // Create two nested transactions. The changes should be committed.
      $this->transactionOuterLayer('A');

      // Because we committed, both of the inserted rows should be present.
      $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'DavidA'])->fetchField();
      $this->assertSame('24', $saved_age, 'Can retrieve DavidA row after commit.');
      $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'DanielA'])->fetchField();
      $this->assertSame('19', $saved_age, 'Can retrieve DanielA row after commit.');
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  /**
   * Tests the compatibility of transactions with DDL statements.
   */
  public function testTransactionWithDdlStatement() {
    // First, test that a commit works normally, even with DDL statements.
    $transaction = $this->connection->startTransaction();
    $this->insertRow('row');
    $this->executeDDLStatement();
    unset($transaction);
    $this->assertRowPresent('row');

    // Even in different order.
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->executeDDLStatement();
    $this->insertRow('row');
    unset($transaction);
    $this->assertRowPresent('row');

    // Even with stacking.
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $transaction2 = $this->connection->startTransaction();
    $this->executeDDLStatement();
    unset($transaction2);
    $transaction3 = $this->connection->startTransaction();
    $this->insertRow('row');
    unset($transaction3);
    unset($transaction);
    $this->assertRowPresent('row');

    // A transaction after a DDL statement should still work the same.
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $transaction2 = $this->connection->startTransaction();
    $this->executeDDLStatement();
    unset($transaction2);
    $transaction3 = $this->connection->startTransaction();
    $this->insertRow('row');
    $transaction3->rollBack();
    unset($transaction3);
    unset($transaction);
    $this->assertRowAbsent('row');

    // The behavior of a rollback depends on the type of database server.
    if ($this->connection->supportsTransactionalDDL()) {
      // For database servers that support transactional DDL, a rollback
      // of a transaction including DDL statements should be possible.
      $this->cleanUp();
      $transaction = $this->connection->startTransaction();
      $this->insertRow('row');
      $this->executeDDLStatement();
      $transaction->rollBack();
      unset($transaction);
      $this->assertRowAbsent('row');

      // Including with stacking.
      $this->cleanUp();
      $transaction = $this->connection->startTransaction();
      $transaction2 = $this->connection->startTransaction();
      $this->executeDDLStatement();
      unset($transaction2);
      $transaction3 = $this->connection->startTransaction();
      $this->insertRow('row');
      unset($transaction3);
      $transaction->rollBack();
      unset($transaction);
      $this->assertRowAbsent('row');
    }
    else {
      // For database servers that do not support transactional DDL,
      // the DDL statement should commit the transaction stack.
      $this->cleanUp();
      $transaction = $this->connection->startTransaction();
      $this->insertRow('row');
      $this->executeDDLStatement();

      try {
        // Rollback the outer transaction.
        $transaction->rollBack();
        // @see \Drupal\mysql\Driver\Database\mysql\TransactionManager::rollbackClientTransaction()
        $this->fail('Rolling back a transaction containing DDL should produce a warning.');
      }
      catch (Warning $warning) {
        $this->assertSame('Rollback attempted when there is no active transaction. This can cause data integrity issues.', $warning->getMessage());
      }
      unset($transaction);
      $this->assertRowPresent('row');
    }
  }

  /**
   * Inserts a single row into the testing table.
   */
  protected function insertRow($name) {
    $this->connection->insert('test')
      ->fields([
        'name' => $name,
      ])
      ->execute();
  }

  /**
   * Executes a DDL statement.
   */
  protected function executeDDLStatement() {
    static $count = 0;
    $table = [
      'fields' => [
        'id' => [
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ];
    $this->connection->schema()->createTable('database_test_' . ++$count, $table);
  }

  /**
   * Starts over for a new test.
   */
  protected function cleanUp() {
    $this->connection->truncate('test')
      ->execute();
    $this->postTransactionCallbackAction = NULL;
  }

  /**
   * Asserts that a given row is present in the test table.
   *
   * @param string $name
   *   The name of the row.
   * @param string $message
   *   The message to log for the assertion.
   *
   * @internal
   */
  public function assertRowPresent(string $name, string $message = NULL): void {
    $present = (boolean) $this->connection->query('SELECT 1 FROM {test} WHERE [name] = :name', [':name' => $name])->fetchField();
    $this->assertTrue($present, $message ?? "Row '{$name}' should be present, but it actually does not exist.");
  }

  /**
   * Asserts that a given row is absent from the test table.
   *
   * @param string $name
   *   The name of the row.
   * @param string $message
   *   The message to log for the assertion.
   *
   * @internal
   */
  public function assertRowAbsent(string $name, string $message = NULL): void {
    $present = (boolean) $this->connection->query('SELECT 1 FROM {test} WHERE [name] = :name', [':name' => $name])->fetchField();
    $this->assertFalse($present, $message ?? "Row '{$name}' should be absent, but it actually exists.");
  }

  /**
   * Tests transaction stacking, commit, and rollback.
   */
  public function testTransactionStacking() {
    // Standard case: pop the inner transaction before the outer transaction.
    $transaction = $this->connection->startTransaction();
    $this->insertRow('outer');
    $transaction2 = $this->connection->startTransaction();
    $this->insertRow('inner');
    // Pop the inner transaction.
    unset($transaction2);
    $this->assertTrue($this->connection->inTransaction(), 'Still in a transaction after popping the inner transaction');
    // Pop the outer transaction.
    unset($transaction);
    $this->assertFalse($this->connection->inTransaction(), 'Transaction closed after popping the outer transaction');
    $this->assertRowPresent('outer');
    $this->assertRowPresent('inner');

    // Pop the transaction in a different order they have been pushed.
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->insertRow('outer');
    $transaction2 = $this->connection->startTransaction();
    $this->insertRow('inner');
    // Pop the outer transaction, nothing should happen.
    unset($transaction);
    $this->insertRow('inner-after-outer-commit');
    $this->assertTrue($this->connection->inTransaction(), 'Still in a transaction after popping the outer transaction');
    // Pop the inner transaction, the whole transaction should commit.
    unset($transaction2);
    $this->assertFalse($this->connection->inTransaction(), 'Transaction closed after popping the inner transaction');
    $this->assertRowPresent('outer');
    $this->assertRowPresent('inner');
    $this->assertRowPresent('inner-after-outer-commit');

    // Rollback the inner transaction.
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->insertRow('outer');
    $transaction2 = $this->connection->startTransaction();
    $this->insertRow('inner');
    // Now rollback the inner transaction.
    $transaction2->rollBack();
    unset($transaction2);
    $this->assertTrue($this->connection->inTransaction(), 'Still in a transaction after popping the outer transaction');
    // Pop the outer transaction, it should commit.
    $this->insertRow('outer-after-inner-rollback');
    unset($transaction);
    $this->assertFalse($this->connection->inTransaction(), 'Transaction closed after popping the inner transaction');
    $this->assertRowPresent('outer');
    $this->assertRowAbsent('inner');
    $this->assertRowPresent('outer-after-inner-rollback');

    // Rollback the inner transaction after committing the outer one.
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->insertRow('outer');
    $transaction2 = $this->connection->startTransaction();
    $this->insertRow('inner');
    // Pop the outer transaction, nothing should happen.
    unset($transaction);
    $this->assertTrue($this->connection->inTransaction(), 'Still in a transaction after popping the outer transaction');
    // Now rollback the inner transaction, it should rollback.
    $transaction2->rollBack();
    unset($transaction2);
    $this->assertFalse($this->connection->inTransaction(), 'Transaction closed after popping the inner transaction');
    $this->assertRowPresent('outer');
    $this->assertRowAbsent('inner');

    // Rollback the outer transaction while the inner transaction is active.
    // In that case, an exception will be triggered because we cannot
    // ensure that the final result will have any meaning.
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->insertRow('outer');
    $transaction2 = $this->connection->startTransaction();
    $this->insertRow('inner');
    $transaction3 = $this->connection->startTransaction();
    $this->insertRow('inner2');
    // Rollback the outer transaction.
    try {
      $transaction->rollBack();
      unset($transaction);
      $this->fail('Rolling back the outer transaction while the inner transaction is active resulted in an exception.');
    }
    catch (TransactionOutOfOrderException $e) {
      // Expected exception; just continue testing.
    }
    $this->assertFalse($this->connection->inTransaction(), 'No more in a transaction after rolling back the outer transaction');
    // Try to commit one inner transaction.
    unset($transaction3);

    // Try to rollback one inner transaction.
    try {
      $transaction->rollBack();
      unset($transaction2);
      $this->fail('Trying to commit an inner transaction resulted in an exception.');
    }
    catch (TransactionNoActiveException $e) {
      // Expected exception; just continue testing.
    }
    $this->assertRowAbsent('outer');
    $this->assertRowAbsent('inner');
    $this->assertRowAbsent('inner2');
  }

  /**
   * Tests that transactions can continue to be used if a query fails.
   */
  public function testQueryFailureInTransaction() {
    $transaction = $this->connection->startTransaction('test_transaction');
    $this->connection->schema()->dropTable('test');

    // Test a failed query using the query() method.
    try {
      $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'David'])->fetchField();
      $this->fail('Using the query method should have failed.');
    }
    catch (\Exception $e) {
      // Just continue testing.
    }

    // Test a failed select query.
    try {
      $this->connection->select('test')
        ->fields('test', ['name'])
        ->execute();

      $this->fail('Select query should have failed.');
    }
    catch (\Exception $e) {
      // Just continue testing.
    }

    // Test a failed insert query.
    try {
      $this->connection->insert('test')
        ->fields([
          'name' => 'David',
          'age' => '24',
        ])
        ->execute();

      $this->fail('Insert query should have failed.');
    }
    catch (\Exception $e) {
      // Just continue testing.
    }

    // Test a failed update query.
    try {
      $this->connection->update('test')
        ->fields(['name' => 'Tiffany'])
        ->condition('id', 1)
        ->execute();

      $this->fail('Update query should have failed.');
    }
    catch (\Exception $e) {
      // Just continue testing.
    }

    // Test a failed delete query.
    try {
      $this->connection->delete('test')
        ->condition('id', 1)
        ->execute();

      $this->fail('Delete query should have failed.');
    }
    catch (\Exception $e) {
      // Just continue testing.
    }

    // Test a failed merge query.
    try {
      $this->connection->merge('test')
        ->key('job', 'Presenter')
        ->fields([
          'age' => '31',
          'name' => 'Tiffany',
        ])
        ->execute();

      $this->fail('Merge query should have failed.');
    }
    catch (\Exception $e) {
      // Just continue testing.
    }

    // Test a failed upsert query.
    try {
      $this->connection->upsert('test')
        ->key('job')
        ->fields(['job', 'age', 'name'])
        ->values([
          'job' => 'Presenter',
          'age' => 31,
          'name' => 'Tiffany',
        ])
        ->execute();

      $this->fail('Upsert query should have failed.');
    }
    catch (\Exception $e) {
      // Just continue testing.
    }

    // Create the missing schema and insert a row.
    $this->installSchema('database_test', ['test']);
    $this->connection->insert('test')
      ->fields([
        'name' => 'David',
        'age' => '24',
      ])
      ->execute();

    // Commit the transaction.
    unset($transaction);

    $saved_age = $this->connection->query('SELECT [age] FROM {test} WHERE [name] = :name', [':name' => 'David'])->fetchField();
    $this->assertEquals('24', $saved_age);
  }

  /**
   * Tests releasing a savepoint before last is safe.
   */
  public function testReleaseIntermediateSavepoint(): void {
    $transaction = $this->connection->startTransaction();
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());
    $savepoint1 = $this->connection->startTransaction();
    $this->assertSame(2, $this->connection->transactionManager()->stackDepth());
    $savepoint2 = $this->connection->startTransaction();
    $this->assertSame(3, $this->connection->transactionManager()->stackDepth());
    $savepoint3 = $this->connection->startTransaction();
    $this->assertSame(4, $this->connection->transactionManager()->stackDepth());
    $savepoint4 = $this->connection->startTransaction();
    $this->assertSame(5, $this->connection->transactionManager()->stackDepth());
    $this->insertRow('row');
    unset($savepoint2);
    $this->assertSame(2, $this->connection->transactionManager()->stackDepth());
    $this->assertRowPresent('row');
    unset($savepoint1);
    unset($transaction);
    $this->assertFalse($this->connection->inTransaction());
    $this->assertRowPresent('row');
  }

  /**
   * Tests for transaction names.
   */
  public function testTransactionName(): void {
    $transaction = $this->connection->startTransaction();
    $this->assertSame('drupal_transaction', $transaction->name());

    $savepoint1 = $this->connection->startTransaction();
    $this->assertSame('savepoint_1', $savepoint1->name());

    $this->expectException(TransactionNameNonUniqueException::class);
    $this->expectExceptionMessage("savepoint_1 is already in use.");
    $savepointFailure = $this->connection->startTransaction('savepoint_1');
  }

  /**
   * Tests that adding a post-transaction callback fails with no transaction.
   */
  public function testRootTransactionEndCallbackAddedWithoutTransaction(): void {
    $this->expectException(\LogicException::class);
    $this->connection->transactionManager()->addPostTransactionCallback([$this, 'rootTransactionCallback']);
  }

  /**
   * Tests post-transaction callback executes after transaction commit.
   */
  public function testRootTransactionEndCallbackCalledOnCommit(): void {
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->connection->transactionManager()->addPostTransactionCallback([$this, 'rootTransactionCallback']);
    $this->insertRow('row');
    $this->assertNull($this->postTransactionCallbackAction);
    unset($transaction);
    $this->assertSame('rtcCommit', $this->postTransactionCallbackAction);
    $this->assertRowPresent('row');
    $this->assertRowPresent('rtcCommit');
  }

  /**
   * Tests post-transaction callback executes after transaction rollback.
   */
  public function testRootTransactionEndCallbackCalledOnRollback(): void {
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->connection->transactionManager()->addPostTransactionCallback([$this, 'rootTransactionCallback']);
    $this->insertRow('row');
    $this->assertNull($this->postTransactionCallbackAction);
    $transaction->rollBack();
    $this->assertSame('rtcRollback', $this->postTransactionCallbackAction);
    unset($transaction);
    $this->assertRowAbsent('row');
    // The row insert should be missing since the client rollback occurs after
    // the processing of the callbacks.
    $this->assertRowAbsent('rtcRollback');
  }

  /**
   * A post-transaction callback for testing purposes.
   */
  public function rootTransactionCallback(bool $success): void {
    $this->postTransactionCallbackAction = $success ? 'rtcCommit' : 'rtcRollback';
    $this->insertRow($this->postTransactionCallbackAction);
  }

  /**
   * Tests deprecation of Connection methods.
   *
   * @group legacy
   */
  public function testConnectionDeprecations(): void {
    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::transactionDepth() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Do not access the transaction stack depth, it is an implementation detail. See https://www.drupal.org/node/3381002');
    $this->assertSame(1, $this->connection->transactionDepth());
    $this->insertRow('row');
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::rollBack() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Do not rollback the connection, roll back the Transaction objects instead. See https://www.drupal.org/node/3381002');
    $this->connection->rollback();
    $transaction = NULL;
    $this->assertRowAbsent('row');

    $this->cleanUp();
    $transaction = $this->connection->startTransaction();
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::addRootTransactionEndCallback() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use TransactionManagerInterface::addPostTransactionCallback() instead. See https://www.drupal.org/node/3381002');
    $this->connection->addRootTransactionEndCallback([$this, 'rootTransactionCallback']);
    $this->insertRow('row');
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::commit() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Do not commit the connection, void the Transaction objects instead. See https://www.drupal.org/node/3381002');
    try {
      $this->connection->commit();
    }
    catch (TransactionExplicitCommitNotAllowedException $e) {
      // Do nothing.
    }
    $transaction = NULL;
    $this->assertRowPresent('row');

    $this->cleanUp();
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::pushTransaction() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use TransactionManagerInterface methods instead. See https://www.drupal.org/node/3381002');
    $this->connection->pushTransaction('foo');
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::popTransaction() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use TransactionManagerInterface methods instead. See https://www.drupal.org/node/3381002');
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::popCommittableTransactions() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use TransactionManagerInterface methods instead. See https://www.drupal.org/node/3381002');
    $this->expectDeprecation('Drupal\\Core\\Database\\Connection::doCommit() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. Use TransactionManagerInterface methods instead. See https://www.drupal.org/node/3381002');
    $this->connection->popTransaction('foo');
  }

}
