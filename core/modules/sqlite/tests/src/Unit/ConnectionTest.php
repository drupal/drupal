<?php

declare(strict_types=1);

namespace Drupal\Tests\sqlite\Unit;

use Drupal\sqlite\Driver\Database\sqlite\Connection;
use Drupal\Tests\Core\Database\Stub\StubPDO;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * @coversDefaultClass \Drupal\sqlite\Driver\Database\sqlite\Connection
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
  public function testCreateConnectionOptionsFromUrl(string $url, string $expected): void {
    $sqlite_connection = new Connection($this->createMock(StubPDO::class), []);
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

  /**
   * Confirms deprecation of the $root argument.
   */
  #[IgnoreDeprecations]
  public function testDeprecationOfRootInConnectionOptionsFromUrl(): void {
    $this->expectDeprecation('Passing the $root value to Drupal\sqlite\Driver\Database\sqlite\Connection::createConnectionOptionsFromUrl() is deprecated in drupal:11.2.0 and will be removed in drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3511287');
    $root = dirname(__DIR__, 8);
    $sqlite_connection = new Connection($this->createMock(StubPDO::class), []);
    $database = $sqlite_connection->createConnectionOptionsFromUrl('sqlite://localhost/tmp/test', $root);
    $this->assertEquals('sqlite', $database['driver']);
    $this->assertEquals('tmp/test', $database['database']);
  }

}
