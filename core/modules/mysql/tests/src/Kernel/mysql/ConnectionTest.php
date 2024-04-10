<?php

declare(strict_types=1);

namespace Drupal\Tests\mysql\Kernel\mysql;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\KernelTests\Core\Database\DriverSpecificDatabaseTestBase;

/**
 * MySQL-specific connection tests.
 *
 * @group Database
 */
class ConnectionTest extends DriverSpecificDatabaseTestBase {

  /**
   * Ensure that you cannot execute multiple statements on MySQL.
   */
  public function testMultipleStatementsForNewPhp(): void {
    $this->expectException(DatabaseExceptionWrapper::class);
    Database::getConnection('default', 'default')->query('SELECT * FROM {test}; SELECT * FROM {test_people}', [], ['allow_delimiter_in_query' => TRUE]);
  }

  /**
   * Tests deprecation of ::makeSequenceName().
   *
   * @group legacy
   */
  public function testMakeSequenceNameDeprecation(): void {
    $this->expectDeprecation("Drupal\\Core\\Database\\Connection::makeSequenceName() is deprecated in drupal:10.2.0 and is removed from drupal:11.0.0. There is no replacement. See https://www.drupal.org/node/3377046");
    $this->assertIsString($this->connection->makeSequenceName('foo', 'bar'));
  }

}
