<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Unit;

use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\sqlite\Driver\Database\sqlite\SqliteConnection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\sqlite\Driver\Database\sqlite\Connection.
 */
#[CoversClass(Connection::class)]
#[Group('Database')]
class ConnectionTest extends UnitTestCase {

  /**
   * Tests create connection options from url.
   *
   * @param string $url
   *   SQLite URL.
   * @param string $expected
   *   Expected connection option.
   */
  #[DataProvider('providerCreateConnectionOptionsFromUrl')]
  public function testCreateConnectionOptionsFromUrl(string $url, string $expected): void {
    $sqlite_connection = new Connection($this->createMock(SqliteConnection::class), []);
    $database = $sqlite_connection->createConnectionOptionsFromUrl($url, NULL);
    $this->assertEquals('sqlite', $database['driver']);
    $this->assertEquals($expected, $database['database']);
  }

  /**
   * Data provider for testCreateConnectionOptionsFromUrl.
   *
   * @return string[][]
   *   Associative array of arrays with the following elements:
   *   - SQLite database URL
   *   - Expected database connection option
   */
  public static function providerCreateConnectionOptionsFromUrl(): array {
    return [
      'sqlite relative path' => ['sqlite://localhost/tmp/test', 'tmp/test'],
      'sqlite absolute path' => ['sqlite://localhost//tmp/test', '/tmp/test'],
      'in memory sqlite path' => ['sqlite://localhost/:memory:', ':memory:'],
    ];
  }

}
