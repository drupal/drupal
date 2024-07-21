<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Transaction;
use Drupal\Core\Database\Transaction\ClientConnectionTransactionState;
use Drupal\Core\Database\Transaction\StackItem;
use Drupal\Core\Database\Transaction\StackItemType;
use Drupal\Core\Database\Transaction\TransactionManagerBase;
use Drupal\Core\Database\TransactionNameNonUniqueException;
use Drupal\Core\Database\TransactionOutOfOrderException;

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
   * Create a root Drupal transaction.
   */
  protected function createRootTransaction(string $name = '', bool $insertRow = TRUE): Transaction {
    $this->assertFalse($this->connection->inTransaction());
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());

    // Start root transaction. Corresponds to 'BEGIN TRANSACTION' on the
    // database.
    $transaction = $this->connection->startTransaction($name);
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());

    // Insert a single row into the testing table.
    if ($insertRow) {
      $this->insertRow('David');
      $this->assertRowPresent('David');
    }

    return $transaction;
  }

  /**
   * Create a Drupal savepoint transaction after root.
   */
  protected function createFirstSavepointTransaction(string $name = '', bool $insertRow = TRUE): Transaction {
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());

    // Starts a savepoint transaction. Corresponds to 'SAVEPOINT savepoint_1'
    // on the database. The name can be changed by the $name argument.
    $savepoint = $this->connection->startTransaction($name);
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(2, $this->connection->transactionManager()->stackDepth());

    // Insert a single row into the testing table.
    if ($insertRow) {
      $this->insertRow('Roger');
      $this->assertRowPresent('Roger');
    }

    return $savepoint;
  }

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
   * Tests root transaction rollback.
   */
  public function testRollbackRoot(): void {
    $transaction = $this->createRootTransaction();

    // Rollback. Since we are at the root, the transaction is closed.
    // Corresponds to 'ROLLBACK' on the database.
    $transaction->rollBack();
    $this->assertRowAbsent('David');
    $this->assertFalse($this->connection->inTransaction());
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
  }

  /**
   * Tests root transaction rollback after savepoint rollback.
   */
  public function testRollbackRootAfterSavepointRollback(): void {
    $transaction = $this->createRootTransaction();
    $savepoint = $this->createFirstSavepointTransaction();

    // Rollback savepoint. It should get released too. Corresponds to 'ROLLBACK
    // TO savepoint_1' plus 'RELEASE savepoint_1' on the database.
    $savepoint->rollBack();
    $this->assertRowPresent('David');
    $this->assertRowAbsent('Roger');
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());

    // Try to rollback root. No savepoint is active, this should succeed.
    $transaction->rollBack();
    $this->assertRowAbsent('David');
    $this->assertRowAbsent('Roger');
    $this->assertFalse($this->connection->inTransaction());
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
  }

  /**
   * Tests root transaction rollback failure when savepoint is open.
   */
  public function testRollbackRootWithActiveSavepoint(): void {
    $transaction = $this->createRootTransaction();
    $savepoint = $this->createFirstSavepointTransaction();

    // Try to rollback root. Since a savepoint is active, this should fail.
    $this->expectException(TransactionOutOfOrderException::class);
    $this->expectExceptionMessageMatches("/^Error attempting rollback of .*\\\\drupal_transaction\\. Active stack: .*\\\\drupal_transaction > .*\\\\savepoint_1/");
    $transaction->rollBack();
  }

  /**
   * Tests savepoint transaction rollback.
   */
  public function testRollbackSavepoint(): void {
    $transaction = $this->createRootTransaction();
    $savepoint = $this->createFirstSavepointTransaction();

    // Rollback savepoint. It should get released too. Corresponds to 'ROLLBACK
    // TO savepoint_1' plus 'RELEASE savepoint_1' on the database.
    $savepoint->rollBack();
    $this->assertRowPresent('David');
    $this->assertRowAbsent('Roger');
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());

    // Insert a row.
    $this->insertRow('Syd');

    // Commit root. Corresponds to 'COMMIT' on the database.
    unset($transaction);
    $this->assertRowPresent('David');
    $this->assertRowAbsent('Roger');
    $this->assertRowPresent('Syd');
    $this->assertFalse($this->connection->inTransaction());
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
  }

  /**
   * Tests savepoint transaction duplicated rollback.
   */
  public function testRollbackTwiceSameSavepoint(): void {
    $transaction = $this->createRootTransaction();
    $savepoint = $this->createFirstSavepointTransaction();

    // Rollback savepoint. It should get released too. Corresponds to 'ROLLBACK
    // TO savepoint_1' plus 'RELEASE savepoint_1' on the database.
    $savepoint->rollBack();
    $this->assertRowPresent('David');
    $this->assertRowAbsent('Roger');
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());

    // Insert a row.
    $this->insertRow('Syd');

    // Rollback savepoint again. Should fail since it was released already.
    try {
      $savepoint->rollBack();
      $this->fail('Expected TransactionOutOfOrderException was not thrown');
    }
    catch (\Exception $e) {
      $this->assertInstanceOf(TransactionOutOfOrderException::class, $e);
      $this->assertMatchesRegularExpression("/^Error attempting rollback of .*\\\\savepoint_1\\. Active stack: .*\\\\drupal_transaction/", $e->getMessage());
    }
    $this->assertRowPresent('David');
    $this->assertRowAbsent('Roger');
    $this->assertRowPresent('Syd');
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(1, $this->connection->transactionManager()->stackDepth());
  }

  /**
   * Tests savepoint transaction rollback failure when later savepoints exist.
   */
  public function testRollbackSavepointWithLaterSavepoint(): void {
    $transaction = $this->createRootTransaction();
    $savepoint1 = $this->createFirstSavepointTransaction();

    // Starts another savepoint transaction. Corresponds to 'SAVEPOINT
    // savepoint_2' on the database.
    $savepoint2 = $this->connection->startTransaction();
    $this->assertTrue($this->connection->inTransaction());
    $this->assertSame(3, $this->connection->transactionManager()->stackDepth());

    // Insert a row.
    $this->insertRow('Syd');
    $this->assertRowPresent('David');
    $this->assertRowPresent('Roger');
    $this->assertRowPresent('Syd');

    // Try to rollback to savepoint 1. Out of order.
    $this->expectException(TransactionOutOfOrderException::class);
    $this->expectExceptionMessageMatches("/^Error attempting rollback of .*\\\\savepoint_1\\. Active stack: .*\\\\drupal_transaction > .*\\\\savepoint_1 > .*\\\\savepoint_2/");
    $savepoint1->rollBack();
  }

  /**
   * Tests a committed transaction.
   *
   * The behavior of this test should be identical for connections that support
   * transactions and those that do not.
   */
  public function testCommittedTransaction(): void {
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
  public function testTransactionWithDdlStatement(): void {
    // First, test that a commit works normally, even with DDL statements.
    $transaction = $this->createRootTransaction('', FALSE);
    $this->insertRow('row');
    $this->executeDDLStatement();
    unset($transaction);
    $this->assertRowPresent('row');

    // Even in different order.
    $this->cleanUp();
    $transaction = $this->createRootTransaction('', FALSE);
    $this->executeDDLStatement();
    $this->insertRow('row');
    unset($transaction);
    $this->assertRowPresent('row');

    // Even with stacking.
    $this->cleanUp();
    $transaction = $this->createRootTransaction('', FALSE);
    $transaction2 = $this->createFirstSavepointTransaction('', FALSE);
    $this->executeDDLStatement();
    unset($transaction2);
    $transaction3 = $this->connection->startTransaction();
    $this->insertRow('row');
    unset($transaction3);
    unset($transaction);
    $this->assertRowPresent('row');

    // A transaction after a DDL statement should still work the same.
    $this->cleanUp();
    $transaction = $this->createRootTransaction('', FALSE);
    $transaction2 = $this->createFirstSavepointTransaction('', FALSE);
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
      $transaction = $this->createRootTransaction('', FALSE);
      $this->insertRow('row');
      $this->executeDDLStatement();
      $transaction->rollBack();
      unset($transaction);
      $this->assertRowAbsent('row');

      // Including with stacking.
      $this->cleanUp();
      $transaction = $this->createRootTransaction('', FALSE);
      $transaction2 = $this->createFirstSavepointTransaction('', FALSE);
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
      $transaction = $this->createRootTransaction('', FALSE);
      $this->insertRow('row');
      $this->executeDDLStatement();

      // Try to rollback the outer transaction. It should fail and void
      // the transaction stack.
      $transaction->rollBack();
      $manager = $this->connection->transactionManager();
      $reflectedTransactionState = new \ReflectionMethod($manager, 'getConnectionTransactionState');
      $this->assertSame(ClientConnectionTransactionState::Voided, $reflectedTransactionState->invoke($manager));

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
  public function assertRowPresent(string $name, ?string $message = NULL): void {
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
  public function assertRowAbsent(string $name, ?string $message = NULL): void {
    $present = (boolean) $this->connection->query('SELECT 1 FROM {test} WHERE [name] = :name', [':name' => $name])->fetchField();
    $this->assertFalse($present, $message ?? "Row '{$name}' should be absent, but it actually exists.");
  }

  /**
   * Tests transaction stacking, commit, and rollback.
   */
  public function testTransactionStacking(): void {
    // Standard case: pop the inner transaction before the outer transaction.
    $transaction = $this->createRootTransaction('', FALSE);
    $this->insertRow('outer');
    $transaction2 = $this->createFirstSavepointTransaction('', FALSE);
    $this->insertRow('inner');
    // Pop the inner transaction.
    unset($transaction2);
    $this->assertTrue($this->connection->inTransaction(), 'Still in a transaction after popping the inner transaction');
    // Pop the outer transaction.
    unset($transaction);
    $this->assertFalse($this->connection->inTransaction(), 'Transaction closed after popping the outer transaction');
    $this->assertRowPresent('outer');
    $this->assertRowPresent('inner');

    // Rollback the inner transaction.
    $this->cleanUp();
    $transaction = $this->createRootTransaction('', FALSE);
    $this->insertRow('outer');
    $transaction2 = $this->createFirstSavepointTransaction('', FALSE);
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
  }

  /**
   * Tests that transactions can continue to be used if a query fails.
   */
  public function testQueryFailureInTransaction(): void {
    $transaction = $this->createRootTransaction('test_transaction', FALSE);
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
    $transaction = $this->createRootTransaction();
    $savepoint1 = $this->createFirstSavepointTransaction('', FALSE);

    // Starts a savepoint transaction. Corresponds to 'SAVEPOINT savepoint_2'
    // on the database.
    $savepoint2 = $this->connection->startTransaction();
    $this->assertSame(3, $this->connection->transactionManager()->stackDepth());
    // Starts a savepoint transaction. Corresponds to 'SAVEPOINT savepoint_3'
    // on the database.
    $savepoint3 = $this->connection->startTransaction();
    $this->assertSame(4, $this->connection->transactionManager()->stackDepth());
    // Starts a savepoint transaction. Corresponds to 'SAVEPOINT savepoint_4'
    // on the database.
    $savepoint4 = $this->connection->startTransaction();
    $this->assertSame(5, $this->connection->transactionManager()->stackDepth());

    $this->insertRow('row');

    // Commit a savepoint transaction. Corresponds to 'RELEASE SAVEPOINT
    // savepoint_2' on the database.
    unset($savepoint2);
    // Since we have committed an intermediate savepoint Transaction object,
    // the savepoints created later have been dropped by the database already.
    $this->assertSame(2, $this->connection->transactionManager()->stackDepth());
    $this->assertRowPresent('row');

    // Commit the remaining Transaction objects. The client transaction is
    // eventually committed.
    unset($savepoint1);
    unset($transaction);
    $this->assertFalse($this->connection->inTransaction());
    $this->assertRowPresent('row');
  }

  /**
   * Tests committing a transaction while savepoints are active.
   */
  public function testCommitWithActiveSavepoint(): void {
    $transaction = $this->createRootTransaction();
    $savepoint1 = $this->createFirstSavepointTransaction('', FALSE);

    // Starts a savepoint transaction. Corresponds to 'SAVEPOINT savepoint_2'
    // on the database.
    $savepoint2 = $this->connection->startTransaction();
    $this->assertSame(3, $this->connection->transactionManager()->stackDepth());

    $this->insertRow('row');

    // Commit the root transaction. Corresponds to 'COMMIT' on the database.
    unset($transaction);
    // Since we have committed the outer (root) Transaction object, the inner
    // (savepoint) ones have been dropped by the database already, and we are
    // no longer in an active transaction state.
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->inTransaction());
    $this->assertRowPresent('row');
    // Unpile the inner (savepoint) Transaction object, it should be a no-op
    // anyway given it was dropped by the database already, and removed from
    // our transaction stack.
    unset($savepoint2);
    $this->assertSame(0, $this->connection->transactionManager()->stackDepth());
    $this->assertFalse($this->connection->inTransaction());
    $this->assertRowPresent('row');
  }

  /**
   * Tests for transaction names.
   */
  public function testTransactionName(): void {
    $transaction = $this->createRootTransaction('', FALSE);
    $this->assertSame('drupal_transaction', $transaction->name());

    $savepoint1 = $this->createFirstSavepointTransaction('', FALSE);
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
    $transaction = $this->createRootTransaction('', FALSE);
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
  public function testRootTransactionEndCallbackCalledAfterRollbackAndDestruction(): void {
    $transaction = $this->createRootTransaction('', FALSE);
    $this->connection->transactionManager()->addPostTransactionCallback([$this, 'rootTransactionCallback']);
    $this->insertRow('row');
    $this->assertNull($this->postTransactionCallbackAction);

    // Callbacks are processed only when destructing the transaction.
    // Executing a rollback is not sufficient by itself.
    $transaction->rollBack();
    $this->assertNull($this->postTransactionCallbackAction);
    $this->assertRowAbsent('rtcCommit');
    $this->assertRowAbsent('rtcRollback');
    $this->assertRowAbsent('row');

    // Destruct the transaction.
    unset($transaction);

    // The post-transaction callback should now have inserted a 'rtcRollback'
    // row.
    $this->assertSame('rtcRollback', $this->postTransactionCallbackAction);
    $this->assertRowAbsent('rtcCommit');
    $this->assertRowPresent('rtcRollback');
    $this->assertRowAbsent('row');
  }

  /**
   * Tests post-transaction callback executes after a DDL statement.
   */
  public function testRootTransactionEndCallbackCalledAfterDdlAndDestruction(): void {
    $transaction = $this->createRootTransaction('', FALSE);
    $this->connection->transactionManager()->addPostTransactionCallback([$this, 'rootTransactionCallback']);
    $this->insertRow('row');
    $this->assertNull($this->postTransactionCallbackAction);

    // Callbacks are processed only when destructing the transaction.
    // Executing a DDL statement is not sufficient itself.
    // We cannot use truncate here, since it has protective code to fall back
    // to a transactional delete when in transaction. We drop an unrelated
    // table instead.
    $this->connection->schema()->dropTable('test_people');
    $this->assertNull($this->postTransactionCallbackAction);
    $this->assertRowAbsent('rtcCommit');
    $this->assertRowAbsent('rtcRollback');
    $this->assertRowPresent('row');

    // Destruct the transaction.
    unset($transaction);

    // The post-transaction callback should now have inserted a 'rtcCommit'
    // row.
    $this->assertSame('rtcCommit', $this->postTransactionCallbackAction);
    $this->assertRowPresent('rtcCommit');
    $this->assertRowAbsent('rtcRollback');
    $this->assertRowPresent('row');
  }

  /**
   * A post-transaction callback for testing purposes.
   */
  public function rootTransactionCallback(bool $success): void {
    $this->postTransactionCallbackAction = $success ? 'rtcCommit' : 'rtcRollback';
    $this->insertRow($this->postTransactionCallbackAction);
  }

  /**
   * Tests TransactionManager failure.
   */
  public function testTransactionManagerFailureOnPendingStackItems(): void {
    $connectionInfo = Database::getConnectionInfo();
    Database::addConnectionInfo('default', 'test_fail', $connectionInfo['default']);
    $testConnection = Database::getConnection('test_fail');

    // Add a fake item to the stack.
    $manager = $testConnection->transactionManager();
    $reflectionMethod = new \ReflectionMethod($manager, 'addStackItem');
    $reflectionMethod->invoke($manager, 'bar', new StackItem('qux', StackItemType::Root));
    // Ensure transaction state can be determined during object destruction.
    // This is necessary for the test to pass when xdebug.mode has the 'develop'
    // option enabled.
    $reflectionProperty = new \ReflectionProperty(TransactionManagerBase::class, 'connectionTransactionState');
    $reflectionProperty->setValue($manager, ClientConnectionTransactionState::Active);

    // Ensure that __destruct() results in an assertion error. Note that this
    // will normally be called by PHP during the object's destruction but Drupal
    // will commit all transactions when a database is closed thereby making
    // this impossible to test unless it is called directly.
    try {
      $manager->__destruct();
      $this->fail("Expected AssertionError error not thrown");
    }
    catch (\AssertionError $e) {
      $this->assertStringStartsWith('Transaction $stack was not empty. Active stack: bar\\qux', $e->getMessage());
    }

    // Clean up.
    $reflectionProperty = new \ReflectionProperty(TransactionManagerBase::class, 'stack');
    $reflectionProperty->setValue($manager, []);
    unset($testConnection);
    Database::closeConnection('test_fail');
  }

}
