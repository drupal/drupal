<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Kernel\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Select query extender classes.
 */
#[CoversClass(Select::class)]
#[Group('Database')]
#[RunTestsInSeparateProcesses]
class SelectExtenderTest extends KernelTestBase {

  /**
   * Data provider for testExtend().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected namespaced class name.
   *   - The database driver namespace.
   *   - The namespaced class name for which to extend.
   */
  public static function providerExtend(): array {
    return [
      [
        'Drupal\search\SearchQuery',
        'Drupal\SearchFake\Driver\Database\SearchFake',
        'Drupal\search\SearchQuery',
      ],
      [
        'Drupal\search\SearchQuery',
        'Drupal\SearchFake\Driver\Database\SearchFake',
        '\Drupal\search\SearchQuery',
      ],
      [
        'Drupal\search\ViewsSearchQuery',
        'Drupal\SearchFake\Driver\Database\SearchFake',
        'Drupal\search\ViewsSearchQuery',
      ],
      [
        'Drupal\search\ViewsSearchQuery',
        'Drupal\SearchFake\Driver\Database\SearchFake',
        '\Drupal\search\ViewsSearchQuery',
      ],
      [
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses\SearchQuery',
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses',
        'Drupal\search\SearchQuery',
      ],
      [
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses\SearchQuery',
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses',
        '\Drupal\search\SearchQuery',
      ],
      [
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses\ViewsSearchQuery',
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses',
        'Drupal\search\ViewsSearchQuery',
      ],
      [
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses\ViewsSearchQuery',
        'Drupal\search_fake\Driver\Database\SearchFakeWithAllCustomClasses',
        '\Drupal\search\ViewsSearchQuery',
      ],
    ];
  }

  /**
   * Tests extend.
   *
   * @legacy-covers ::extend
   * @legacy-covers \Drupal\Core\Database\Query\SelectExtender::extend
   */
  #[DataProvider('providerExtend')]
  public function testExtend(string $expected, string $namespace, string $extend): void {
    $additional_class_loader = new ClassLoader();
    $additional_class_loader->addPsr4("Drupal\\search_fake\\Driver\\Database\\SearchFake\\", __DIR__ . "/../../../modules/search_fake/src/Driver/Database/SearchFake");
    $additional_class_loader->addPsr4("Drupal\\search_fake\\Driver\\Database\\SearchFakeWithAllCustomClasses\\", __DIR__ . "/../../../modules/search_fake/src/Driver/Database/SearchFakeWithAllCustomClasses");
    $additional_class_loader->register(TRUE);

    $connection = new StubConnection($this->createStub(\PDO::class), ['namespace' => $namespace]);

    // Tests the method \Drupal\Core\Database\Query\Select::extend().
    $select = $connection->select('test')->extend($extend);
    $this->assertEquals($expected, get_class($select));

    // Get an instance of the class \Drupal\Core\Database\Query\SelectExtender.
    $select_extender = $connection->select('test')->extend(SelectExtender::class);
    $this->assertEquals(SelectExtender::class, get_class($select_extender));

    // Tests the method \Drupal\Core\Database\Query\SelectExtender::extend().
    $select_extender_extended = $select_extender->extend($extend);
    $this->assertEquals($expected, get_class($select_extender_extended));
  }

}
