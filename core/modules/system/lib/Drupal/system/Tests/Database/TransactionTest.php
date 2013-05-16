<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Database\TransactionTest.
 */

namespace Drupal\system\Tests\Database;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\TransactionOutOfOrderException;
use Drupal\Core\Database\TransactionNoActiveException;

/**
 * Tests transaction support, particularly nesting.
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
 */
class TransactionTest extends DatabaseTestBase {

  public static function getInfo() {
    return array(
      'name' => 'Transaction tests',
      'description' => 'Test the transaction abstraction system.',
      'group' => 'Database',
    );
  }

  /**
   * Encapsulates a transaction's "inner layer" with an "outer layer".
   *
   * This "outer layer" transaction starts and then encapsulates the "inner
   * layer" transaction. This nesting is used to evaluate whether the the
   * database transaction API properly supports nesting. By "properly supports,"
   * we mean the outer transaction continues to exist regardless of what
   * functions are called and whether those functions start their own
   * transactions.
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
    $connection = Database::getConnection();
    $depth = $connection->transactionDepth();
    $txn = db_transaction();

    // Insert a single row into the testing table.
    db_insert('test')
      ->fields(array(
        'name' => 'David' . $suffix,
        'age' => '24',
      ))
      ->execute();

    $this->assertTrue($connection->inTransaction(), 'In transaction before calling nested transaction.');

    // We're already in a transaction, but we call ->transactionInnerLayer
    // to nest another transaction inside the current one.
    $this->transactionInnerLayer($suffix, $rollback, $ddl_statement);

    $this->assertTrue($connection->inTransaction(), 'In transaction after calling nested transaction.');

    if ($rollback) {
      // Roll back the transaction, if requested.
      // This rollback should propagate to the last savepoint.
      $txn->rollback();
      $this->assertTrue(($connection->transactionDepth() == $depth), 'Transaction has rolled back to the last savepoint after calling rollback().');
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
    $connection = Database::getConnection();

    $depth = $connection->transactionDepth();
    // Start a transaction. If we're being called from ->transactionOuterLayer,
    // then we're already in a transaction. Normally, that would make starting
    // a transaction here dangerous, but the database API handles this problem
    // for us by tracking the nesting and avoiding the danger.
    $txn = db_transaction();

    $depth2 = $connection->transactionDepth();
    $this->assertTrue($depth < $depth2, 'Transaction depth is has increased with new transaction.');

    // Insert a single row into the testing table.
    db_insert('test')
      ->fields(array(
        'name' => 'Daniel' . $suffix,
        'age' => '19',
      ))
      ->execute();

    $this->assertTrue($connection->inTransaction(), 'In transaction inside nested transaction.');

    if ($ddl_statement) {
      $table = array(
        'fields' => array(
          'id' => array(
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
          ),
        ),
        'primary key' => array('id'),
      );
      db_create_table('database_test_1', $table);

      $this->assertTrue($connection->inTransaction(), 'In transaction inside nested transaction.');
    }

    if ($rollback) {
      // Roll back the transaction, if requested.
      // This rollback should propagate to the last savepoint.
      $txn->rollback();
      $this->assertTrue(($connection->transactionDepth() == $depth), 'Transaction has rolled back to the last savepoint after calling rollback().');
    }
  }

  /**
   * Tests transaction rollback on a database that supports transactions.
   *
   * If the active connection does not support transactions, this test does
   * nothing.
   */
  function testTransactionRollBackSupported() {
    // This test won't work right if transactions are not supported.
    if (!Database::getConnection()->supportsTransactions()) {
      return;
    }
    try {
      // Create two nested transactions. Roll back from the inner one.
      $this->transactionOuterLayer('B', TRUE);

      // Neither of the rows we inserted in the two transaction layers
      // should be present in the tables post-rollback.
      $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'DavidB'))->fetchField();
      $this->assertNotIdentical($saved_age, '24', 'Cannot retrieve DavidB row after commit.');
      $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'DanielB'))->fetchField();
      $this->assertNotIdentical($saved_age, '19', 'Cannot retrieve DanielB row after commit.');
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  /**
   * Tests transaction rollback on a database that doesn't support transactions.
   *
   * If the active driver supports transactions, this test does nothing.
   */
  function testTransactionRollBackNotSupported() {
    // This test won't work right if transactions are supported.
    if (Database::getConnection()->supportsTransactions()) {
      return;
    }
    try {
      // Create two nested transactions. Attempt to roll back from the inner one.
      $this->transactionOuterLayer('B', TRUE);

      // Because our current database claims to not support transactions,
      // the inserted rows should be present despite the attempt to roll back.
      $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'DavidB'))->fetchField();
      $this->assertIdentical($saved_age, '24', 'DavidB not rolled back, since transactions are not supported.');
      $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'DanielB'))->fetchField();
      $this->assertIdentical($saved_age, '19', 'DanielB not rolled back, since transactions are not supported.');
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
  function testCommittedTransaction() {
    try {
      // Create two nested transactions. The changes should be committed.
      $this->transactionOuterLayer('A');

      // Because we committed, both of the inserted rows should be present.
      $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'DavidA'))->fetchField();
      $this->assertIdentical($saved_age, '24', 'Can retrieve DavidA row after commit.');
      $saved_age = db_query('SELECT age FROM {test} WHERE name = :name', array(':name' => 'DanielA'))->fetchField();
      $this->assertIdentical($saved_age, '19', 'Can retrieve DanielA row after commit.');
    }
    catch (\Exception $e) {
      $this->fail($e->getMessage());
    }
  }

  /**
   * Tests the compatibility of transactions with DDL statements.
   */
  function testTransactionWithDdlStatement() {
    // First, test that a commit works normally, even with DDL statements.
    $transaction = db_transaction();
    $this->insertRow('row');
    $this->executeDDLStatement();
    unset($transaction);
    $this->assertRowPresent('row');

    // Even in different order.
    $this->cleanUp();
    $transaction = db_transaction();
    $this->executeDDLStatement();
    $this->insertRow('row');
    unset($transaction);
    $this->assertRowPresent('row');

    // Even with stacking.
    $this->cleanUp();
    $transaction = db_transaction();
    $transaction2 = db_transaction();
    $this->executeDDLStatement();
    unset($transaction2);
    $transaction3 = db_transaction();
    $this->insertRow('row');
    unset($transaction3);
    unset($transaction);
    $this->assertRowPresent('row');

    // A transaction after a DDL statement should still work the same.
    $this->cleanUp();
    $transaction = db_transaction();
    $transaction2 = db_transaction();
    $this->executeDDLStatement();
    unset($transaction2);
    $transaction3 = db_transaction();
    $this->insertRow('row');
    $transaction3->rollback();
    unset($transaction3);
    unset($transaction);
    $this->assertRowAbsent('row');

    // The behavior of a rollback depends on the type of database server.
    if (Database::getConnection()->supportsTransactionalDDL()) {
      // For database servers that support transactional DDL, a rollback
      // of a transaction including DDL statements should be possible.
      $this->cleanUp();
      $transaction = db_transaction();
      $this->insertRow('row');
      $this->executeDDLStatement();
      $transaction->rollback();
      unset($transaction);
      $this->assertRowAbsent('row');

      // Including with stacking.
      $this->cleanUp();
      $transaction = db_transaction();
      $transaction2 = db_transaction();
      $this->executeDDLStatement();
      unset($transaction2);
      $transaction3 = db_transaction();
      $this->insertRow('row');
      unset($transaction3);
      $transaction->rollback();
      unset($transaction);
      $this->assertRowAbsent('row');
    }
    else {
      // For database servers that do not support transactional DDL,
      // the DDL statement should commit the transaction stack.
      $this->cleanUp();
      $transaction = db_transaction();
      $this->insertRow('row');
      $this->executeDDLStatement();
      // Rollback the outer transaction.
      try {
        $transaction->rollback();
        unset($transaction);
        // @TODO: an exception should be triggered here, but is not, because
        // "ROLLBACK" fails silently in MySQL if there is no transaction active.
        // $this->fail(t('Rolling back a transaction containing DDL should fail.'));
      }
      catch (TransactionNoActiveException $e) {
        $this->pass('Rolling back a transaction containing DDL should fail.');
      }
      $this->assertRowPresent('row');
    }
  }

  /**
   * Inserts a single row into the testing table.
   */
  protected function insertRow($name) {
    db_insert('test')
      ->fields(array(
        'name' => $name,
      ))
      ->execute();
  }

  /**
   * Executes a DDL statement.
   */
  protected function executeDDLStatement() {
    static $count = 0;
    $table = array(
      'fields' => array(
        'id' => array(
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
      ),
      'primary key' => array('id'),
    );
    db_create_table('database_test_' . ++$count, $table);
  }

  /**
   * Starts over for a new test.
   */
  protected function cleanUp() {
    db_truncate('test')
      ->execute();
  }

  /**
   * Asserts that a given row is present in the test table.
   *
   * @param $name
   *   The name of the row.
   * @param $message
   *   The message to log for the assertion.
   */
  function assertRowPresent($name, $message = NULL) {
    if (!isset($message)) {
      $message = format_string('Row %name is present.', array('%name' => $name));
    }
    $present = (boolean) db_query('SELECT 1 FROM {test} WHERE name = :name', array(':name' => $name))->fetchField();
    return $this->assertTrue($present, $message);
  }

  /**
   * Asserts that a given row is absent from the test table.
   *
   * @param $name
   *   The name of the row.
   * @param $message
   *   The message to log for the assertion.
   */
  function assertRowAbsent($name, $message = NULL) {
    if (!isset($message)) {
      $message = format_string('Row %name is absent.', array('%name' => $name));
    }
    $present = (boolean) db_query('SELECT 1 FROM {test} WHERE name = :name', array(':name' => $name))->fetchField();
    return $this->assertFalse($present, $message);
  }

  /**
   * Tests transaction stacking, commit, and rollback.
   */
  function testTransactionStacking() {
    // This test won't work right if transactions are not supported.
    if (!Database::getConnection()->supportsTransactions()) {
      return;
    }

    $database = Database::getConnection();

    // Standard case: pop the inner transaction before the outer transaction.
    $transaction = db_transaction();
    $this->insertRow('outer');
    $transaction2 = db_transaction();
    $this->insertRow('inner');
    // Pop the inner transaction.
    unset($transaction2);
    $this->assertTrue($database->inTransaction(), 'Still in a transaction after popping the inner transaction');
    // Pop the outer transaction.
    unset($transaction);
    $this->assertFalse($database->inTransaction(), 'Transaction closed after popping the outer transaction');
    $this->assertRowPresent('outer');
    $this->assertRowPresent('inner');

    // Pop the transaction in a different order they have been pushed.
    $this->cleanUp();
    $transaction = db_transaction();
    $this->insertRow('outer');
    $transaction2 = db_transaction();
    $this->insertRow('inner');
    // Pop the outer transaction, nothing should happen.
    unset($transaction);
    $this->insertRow('inner-after-outer-commit');
    $this->assertTrue($database->inTransaction(), 'Still in a transaction after popping the outer transaction');
    // Pop the inner transaction, the whole transaction should commit.
    unset($transaction2);
    $this->assertFalse($database->inTransaction(), 'Transaction closed after popping the inner transaction');
    $this->assertRowPresent('outer');
    $this->assertRowPresent('inner');
    $this->assertRowPresent('inner-after-outer-commit');

    // Rollback the inner transaction.
    $this->cleanUp();
    $transaction = db_transaction();
    $this->insertRow('outer');
    $transaction2 = db_transaction();
    $this->insertRow('inner');
    // Now rollback the inner transaction.
    $transaction2->rollback();
    unset($transaction2);
    $this->assertTrue($database->inTransaction(), 'Still in a transaction after popping the outer transaction');
    // Pop the outer transaction, it should commit.
    $this->insertRow('outer-after-inner-rollback');
    unset($transaction);
    $this->assertFalse($database->inTransaction(), 'Transaction closed after popping the inner transaction');
    $this->assertRowPresent('outer');
    $this->assertRowAbsent('inner');
    $this->assertRowPresent('outer-after-inner-rollback');

    // Rollback the inner transaction after committing the outer one.
    $this->cleanUp();
    $transaction = db_transaction();
    $this->insertRow('outer');
    $transaction2 = db_transaction();
    $this->insertRow('inner');
    // Pop the outer transaction, nothing should happen.
    unset($transaction);
    $this->assertTrue($database->inTransaction(), 'Still in a transaction after popping the outer transaction');
    // Now rollback the inner transaction, it should rollback.
    $transaction2->rollback();
    unset($transaction2);
    $this->assertFalse($database->inTransaction(), 'Transaction closed after popping the inner transaction');
    $this->assertRowPresent('outer');
    $this->assertRowAbsent('inner');

    // Rollback the outer transaction while the inner transaction is active.
    // In that case, an exception will be triggered because we cannot
    // ensure that the final result will have any meaning.
    $this->cleanUp();
    $transaction = db_transaction();
    $this->insertRow('outer');
    $transaction2 = db_transaction();
    $this->insertRow('inner');
    $transaction3 = db_transaction();
    $this->insertRow('inner2');
    // Rollback the outer transaction.
    try {
      $transaction->rollback();
      unset($transaction);
      $this->fail('Rolling back the outer transaction while the inner transaction is active resulted in an exception.');
    }
    catch (TransactionOutOfOrderException $e) {
      $this->pass('Rolling back the outer transaction while the inner transaction is active resulted in an exception.');
    }
    $this->assertFalse($database->inTransaction(), 'No more in a transaction after rolling back the outer transaction');
    // Try to commit one inner transaction.
    unset($transaction3);
    $this->pass('Trying to commit an inner transaction resulted in an exception.');
    // Try to rollback one inner transaction.
    try {
      $transaction->rollback();
      unset($transaction2);
      $this->fail('Trying to commit an inner transaction resulted in an exception.');
    }
    catch (TransactionNoActiveException $e) {
      $this->pass('Trying to commit an inner transaction resulted in an exception.');
    }
    $this->assertRowAbsent('outer');
    $this->assertRowAbsent('inner');
    $this->assertRowAbsent('inner2');
  }
}

