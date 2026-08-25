<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Unit\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Connection;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the connections for the Search queries.
 */
#[CoversClass(Connection::class)]
#[Group('search')]
class ConnectionTest extends UnitTestCase {

  /**
   * Data provider for testGetDriverClass().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected namespaced class name.
   *   - Namespace.
   *   - Class name without namespace.
   */
  public static function providerGetDriverClass(): array {
    return [
      [
        'SearchQuery',
        'Drupal\SearchFake\Driver\Database\SearchFake',
        'SearchQuery',
      ],
      [
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses\SearchQuery',
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses',
        'SearchQuery',
      ],
      [
        'Drupal\search\SearchQuery',
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses',
        'Drupal\search\SearchQuery',
      ],
      [
        '\Drupal\search\SearchQuery',
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses',
        '\Drupal\search\SearchQuery',
      ],
    ];
  }

  /**
   * Tests get driver class.
   */
  #[DataProvider('providerGetDriverClass')]
  public function testGetDriverClass(string $expected, ?string $namespace, string $class): void {
    $additional_class_loader = new ClassLoader();
    $additional_class_loader->addPsr4("Drupal\\search_fake\\Driver\\Database\\SearchFake\\", __DIR__ . "/../../../modules/search_fake/src/Driver/Database/SearchFake");
    $additional_class_loader->addPsr4("Drupal\\search_fake\\Driver\\Database\\SearchFakeWithAllCustomClasses\\", __DIR__ . "/../../../modules/search_fake/src/Driver/Database/SearchFakeWithAllCustomClasses");
    $additional_class_loader->register(TRUE);

    $connection = new StubConnection($this->createStub(\PDO::class), ['namespace' => $namespace]);
    match ($class) {
      'Install\\Tasks',
      'ExceptionHandler',
      'Select',
      'Insert',
      'Merge',
      'Upsert',
      'Update',
      'Delete',
      'Truncate',
      'Schema',
      'Condition',
      'Transaction' => $this->expectExceptionMessageIs('Calling Drupal\\Core\\Database\\Connection::getDriverClass() for \'' . $class . '\' is not supported. Use standard autoloading in the methods that return database operations. See https://www.drupal.org/node/3217534'),
      default => NULL,
    };
    $this->assertEquals($expected, $connection->getDriverClass($class));
  }

}
