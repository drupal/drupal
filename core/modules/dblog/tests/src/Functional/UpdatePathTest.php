<?php

declare(strict_types=1);

namespace Drupal\Tests\dblog\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update functions for the Database Logging module.
 *
 * @group dblog
 */
class UpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that, after update 10101, the 'wid' column can be a 64-bit integer.
   */
  public function testLogEntryWithBigId(): void {
    if (PHP_INT_SIZE < 8) {
      $this->markTestSkipped('This test can only be run on a system that supports 64-bit integers (i.e., PHP_INT_SIZE is 8).');
    }
    $this->runUpdates();

    global $base_root;

    $connection = Database::getConnection();
    // Insert a row with a big value for wid.
    $insert = $connection->insert('watchdog');
    $insert->fields([
      'wid'         => 2147483647000,
      'message'     => 'Dblog test log message with big WID',
      'type'        => 'test',
      'variables'   => '',
      'severity'    => RfcLogLevel::NOTICE,
      'uid'         => 1,
      'location'    => $base_root . \Drupal::request()->getRequestUri(),
      'hostname'    => $base_root,
      'timestamp'   => \Drupal::time()->getRequestTime(),
    ]);
    $insert->execute();

    // Insert another row without a value for wid, to test auto-increment.
    $insert = $connection->insert('watchdog');
    $insert->fields([
      'message'     => 'Dblog test log message with big WID',
      'type'        => 'test',
      'variables'   => '',
      'severity'    => RfcLogLevel::NOTICE,
      'uid'         => 1,
      'location'    => $base_root . \Drupal::request()->getRequestUri(),
      'hostname'    => $base_root,
      'timestamp'   => \Drupal::time()->getRequestTime(),
    ]);
    $insert->execute();

    // Test that the first row exists with the expected value for wid.
    $result = $connection->select('watchdog')
      ->fields('watchdog', ['wid'])
      ->condition('wid', 2147483647000)
      ->execute()
      ->fetchAssoc();
    $this->assertNotEmpty($result, 'The row with a big value for wid exists.');

    // Test that the second row exists with the expected value for wid.
    $result = $connection->select('watchdog')
      ->fields('watchdog', ['wid'])
      ->condition('wid', 2147483647000 + 1)
      ->execute()
      ->fetchAssoc();
    $this->assertNotEmpty($result, 'The row without a value for wid exists, and has the correct auto-incremented value for wid.');
  }

}
