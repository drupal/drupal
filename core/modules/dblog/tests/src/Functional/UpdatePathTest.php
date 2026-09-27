<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Functional;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Database\IntegrityConstraintViolationException;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests update functions for the Database Logging module.
 */
#[Group('dblog')]
#[RunTestsInSeparateProcesses]
class UpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that, after update 12100, the 'wid' column is an unsigned integer.
   */
  public function testLogEntryWithNegativeId(): void {
    $connection = Database::getConnection();

    // Insert a normal row first. On PostgreSQL, inserting an explicit value
    // into a serial column resyncs the sequence to GREATEST(MAX(wid), <value>),
    // which fails while the table is empty.
    $this->insertLogEntry($connection);

    // Before updates, a negative WID should be possible.
    $this->insertLogEntry($connection, -1000);
    $this->assertEquals(1, $connection->select('watchdog')
      ->condition('wid', 0, '<')
      ->countQuery()
      ->execute()
      ->fetchField());

    $this->runUpdates();

    // Check the primary key.
    $method = new \ReflectionMethod($connection->schema(), 'findPrimaryKeyColumns');
    $this->assertEquals(['wid'], $method->invoke($connection->schema(), 'watchdog'));

    // After updates, a negative WID should not be allowed.
    try {
      $this->insertLogEntry($connection, -1000);
      $this->fail('After updates, a negative WID should not be allowed.');
    }
    catch (IntegrityConstraintViolationException | DatabaseExceptionWrapper) {
      // Expected.
    }
  }

  /**
   * Inserts a row into the watchdog table.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param int|null $wid
   *   An explicit watchdog ID, or NULL to let the sequence assign one.
   */
  private function insertLogEntry(Connection $connection, ?int $wid = NULL): void {
    global $base_root;

    $fields = [
      'message' => 'Dblog test log message',
      'type' => 'test',
      'variables' => '',
      'severity' => RfcLogLevel::NOTICE,
      'uid' => 1,
      'location' => $base_root . \Drupal::request()->getRequestUri(),
      'hostname' => $base_root,
      'timestamp' => \Drupal::time()->getRequestTime(),
    ];
    if ($wid !== NULL) {
      $fields['wid'] = $wid;
    }
    $connection->insert('watchdog')->fields($fields)->execute();
  }

}
