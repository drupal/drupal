<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Database;

use Drupal\Core\Database\Database;

/**
 * Tests the temporary query functionality.
 *
 * @group Database
 */
abstract class TemporaryQueryTestBase extends DriverSpecificDatabaseTestBase {

  /**
   * Returns the connection.
   */
  public function getConnection() {
    return Database::getConnection();
  }

  /**
   * Returns the number of rows of a table.
   */
  public function countTableRows($table_name) {
    return Database::getConnection()->select($table_name)->countQuery()->execute()->fetchField();
  }

  /**
   * Confirms that temporary tables work.
   */
  public function testTemporaryQuery(): void {
    $connection = $this->getConnection();

    // Now try to run two temporary queries in the same request.
    $table_name_test = $connection->queryTemporary('SELECT [name] FROM {test}', []);
    $table_name_task = $connection->queryTemporary('SELECT [pid] FROM {test_task}', []);

    $this->assertEquals($this->countTableRows('test'), $this->countTableRows($table_name_test), 'A temporary table was created successfully in this request.');
    $this->assertEquals($this->countTableRows('test_task'), $this->countTableRows($table_name_task), 'A second temporary table was created successfully in this request.');

    // Check that leading whitespace and comments do not cause problems
    // in the modified query.
    $sql = "
      -- Let's select some rows into a temporary table
      SELECT [name] FROM {test}
    ";
    $table_name_test = $connection->queryTemporary($sql, []);
    $this->assertEquals($this->countTableRows('test'), $this->countTableRows($table_name_test), 'Leading white space and comments do not interfere with temporary table creation.');
  }

}
