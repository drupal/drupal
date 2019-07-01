<?php

namespace Drupal\KernelTests\Core\Database;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseException;

/**
 * Tests handling of large queries.
 *
 * @group Database
 */
class LargeQueryTest extends DatabaseTestBase {

  /**
   * Tests truncation of messages when max_allowed_packet exception occurs.
   */
  public function testMaxAllowedPacketQueryTruncating() {
    // Only run this test for the 'mysql' driver.
    $driver = $this->connection->driver();
    if ($driver !== 'mysql') {
      $this->markTestSkipped("MySql tests can not run for driver '$driver'.");
    }
    // The max_allowed_packet value is configured per database instance.
    // Retrieve the max_allowed_packet value from the current instance and
    // check if PHP is configured with sufficient allowed memory to be able
    // to generate a query larger than max_allowed_packet.
    $max_allowed_packet = $this->connection->query('SELECT @@global.max_allowed_packet')->fetchField();
    if (!Environment::checkMemoryLimit($max_allowed_packet + (16 * 1024 * 1024))) {
      $this->markTestSkipped('The configured max_allowed_packet exceeds the php memory limit. Therefore the test is skipped.');
    }

    $long_name = str_repeat('a', $max_allowed_packet + 1);
    try {
      $this->connection->query('SELECT name FROM {test} WHERE name = :name', [':name' => $long_name]);
      $this->fail("An exception should be thrown for queries larger than 'max_allowed_packet'");
    }
    catch (DatabaseException $e) {
      // Close and re-open the connection. Otherwise we will run into error
      // 2006 "MySQL server had gone away" afterwards.
      Database::closeConnection();
      Database::getConnection();
      // Got a packet bigger than 'max_allowed_packet' bytes exception thrown.
      $this->assertEquals(1153, $e->getPrevious()->errorInfo[1]);
      // 'max_allowed_packet' exception message truncated.
      // Use strlen() to count the bytes exactly, not the unicode chars.
      $this->assertLessThanOrEqual($max_allowed_packet, strlen($e->getMessage()));
    }
  }

}
