<?php

declare(strict_types=1);

namespace Drupal\Tests\mysqli\Kernel\mysqli;

use Drupal\Component\Utility\Environment;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Tests\mysql\Kernel\mysql\LargeQueryTest as BaseMySqlTest;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests handling of large queries.
 */
#[Group('Database')]
class LargeQueryTest extends BaseMySqlTest {

  /**
   * Tests truncation of messages when max_allowed_packet exception occurs.
   */
  public function testMaxAllowedPacketQueryTruncating(): void {
    $connectionInfo = Database::getConnectionInfo();
    Database::addConnectionInfo('default', 'testMaxAllowedPacketQueryTruncating', $connectionInfo['default']);
    $testConnection = Database::getConnection('testMaxAllowedPacketQueryTruncating');

    // The max_allowed_packet value is configured per database instance.
    // Retrieve the max_allowed_packet value from the current instance and
    // check if PHP is configured with sufficient allowed memory to be able
    // to generate a query larger than max_allowed_packet.
    $max_allowed_packet = $testConnection->query('SELECT @@global.max_allowed_packet')->fetchField();
    if (!Environment::checkMemoryLimit($max_allowed_packet + (16 * 1024 * 1024))) {
      $this->markTestSkipped('The configured max_allowed_packet exceeds the php memory limit. Therefore the test is skipped.');
    }

    $long_name = str_repeat('a', $max_allowed_packet + 1);
    try {
      $testConnection->query('SELECT [name] FROM {test} WHERE [name] = :name', [':name' => $long_name]);
      $this->fail("An exception should be thrown for queries larger than 'max_allowed_packet'");
    }
    catch (\Throwable $e) {
      Database::closeConnection('testMaxAllowedPacketQueryTruncating');
      // Got a packet bigger than 'max_allowed_packet' bytes exception thrown.
      $this->assertInstanceOf(DatabaseExceptionWrapper::class, $e);
      $this->assertEquals(1153, $e->getPrevious()->getCode());
      // 'max_allowed_packet' exception message truncated.
      // Use strlen() to count the bytes exactly, not the Unicode chars.
      $this->assertLessThanOrEqual($max_allowed_packet, strlen($e->getMessage()));
    }
  }

}
