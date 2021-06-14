<?php

namespace Drupal\Tests\Core\Database\Driver\sqlite;

use Drupal\Core\Database\Driver\sqlite\Connection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Database\Driver\sqlite\Connection
 * @group Database
 */
class ConnectionTest extends UnitTestCase {

  /**
   * @covers ::createConnectionOptionsFromUrl
   * @dataProvider providerCreateConnectionOptionsFromUrl
   *
   * @param string $url
   *   SQLite URL.
   * @param string $expected
   *   Expected connection option.
   */
  public function testCreateConnectionOptionsFromUrl(string $url, string $expected) {
    $root = dirname(__DIR__, 8);
    $sqlite_connection = new Connection($this->createMock(StubPDO::class), []);
    $database = $sqlite_connection->createConnectionOptionsFromUrl($url, $root);
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
  public function providerCreateConnectionOptionsFromUrl(): array {
    $root = dirname(__DIR__, 8);
    return [
      'sqlite relative path' => ['sqlite://localhost/tmp/test', $root . '/tmp/test'],
      'sqlite absolute path' => ['sqlite://localhost//tmp/test', '/tmp/test'],
      'in memory sqlite path' => ['sqlite://localhost/:memory:', ':memory:'],
    ];
  }

}
