<?php

declare(strict_types=1);

namespace Drupal\Tests\package_manager\Kernel\PathExcluder;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\package_manager\Event\CollectPathsToExcludeEvent;
use Drupal\package_manager\PathExcluder\SqliteDatabaseExcluder;
use Drupal\package_manager\PathLocator;
use Drupal\Tests\package_manager\Kernel\PackageManagerKernelTestBase;
use PhpTuf\ComposerStager\API\Path\Factory\PathFactoryInterface;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @covers \Drupal\package_manager\PathExcluder\SqliteDatabaseExcluder
 * @group package_manager
 * @internal
 */
class SqliteDatabaseExcluderTest extends PackageManagerKernelTestBase {

  /**
   * The mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection|\Prophecy\Prophecy\ObjectProphecy
   */
  private Connection|ObjectProphecy $mockDatabase;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    parent::register($container);

    $this->mockDatabase = $this->prophesize(Connection::class);
    $this->mockDatabase->driver()
      ->willReturn('sqlite')
      ->shouldBeCalled();
    $container->set('mock_database', $this->mockDatabase->reveal());

    $container->getDefinition(SqliteDatabaseExcluder::class)
      ->setArgument('$database', new Reference('mock_database'));
  }

  /**
   * Data provider for ::testSqliteDatabaseFilesExcluded().
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerSqliteDatabaseFilesExcluded(): array {
    return [
      // If the database is at a relative path, it should be excluded relative
      // to the web root.
      'relative path in relocated web root' => [
        'www',
        'db.sqlite',
        'www/db.sqlite',
      ],
      'relative path, web root is project root' => [
        '',
        'db.sqlite',
        'db.sqlite',
      ],
      // If the database is at an absolute path in the project root, it should
      // be excluded relative to the project root.
      'absolute path in relocated web root' => [
        'www',
        '<PROJECT_ROOT>/www/db.sqlite',
        'www/db.sqlite',
      ],
      'absolute path, web root is project root' => [
        '',
        '<PROJECT_ROOT>/db.sqlite',
        'db.sqlite',
      ],
      // If the database is outside the project root, the excluder doesn't need
      // to do anything.
      'absolute path outside of project, relocated web root' => [
        'www',
        '/path/to/database.sqlite',
        FALSE,
      ],
      'absolute path outside of project, web root is project root' => [
        '',
        '/path/to/database.sqlite',
        FALSE,
      ],
    ];
  }

  /**
   * Tests that SQLite database files are excluded from stage operations.
   *
   * @param string $web_root
   *   The web root that should be returned by the path locator. See
   *   \Drupal\package_manager\PathLocator::getWebRoot().
   * @param string $db_path
   *   The path of the SQLite database, as it should be reported by the database
   *   connection. This can be a relative or absolute path; it does not need to
   *   actually exist.
   * @param string|false $expected_excluded_path
   *   The path to the database, as it should be given to
   *   CollectPathsToExcludeEvent. If FALSE, the database is located outside the
   *   project and therefore is not excluded.
   *
   * @dataProvider providerSqliteDatabaseFilesExcluded
   */
  public function testSqliteDatabaseFilesExcluded(string $web_root, string $db_path, string|false $expected_excluded_path): void {
    /** @var \Drupal\package_manager_bypass\MockPathLocator $path_locator */
    $path_locator = $this->container->get(PathLocator::class);
    $project_root = $path_locator->getProjectRoot();

    // Set the mocked web root, keeping everything else as-is.
    $path_locator->setPaths(
      $project_root,
      $path_locator->getVendorDirectory(),
      $web_root,
      $path_locator->getStagingRoot(),
    );
    $db_path = str_replace('<PROJECT_ROOT>', $project_root, $db_path);
    $this->mockDatabase->getConnectionOptions()
      ->willReturn(['database' => $db_path])
      ->shouldBeCalled();

    $event = new CollectPathsToExcludeEvent(
      $this->createStage(),
      $path_locator,
      $this->container->get(PathFactoryInterface::class),
    );
    $actual_excluded_paths = $this->container->get('event_dispatcher')
      ->dispatch($event)
      ->getAll();

    if (is_string($expected_excluded_path)) {
      $expected_exclusions = [
        $expected_excluded_path,
        $expected_excluded_path . '-shm',
        $expected_excluded_path . '-wal',
      ];
      $this->assertEmpty(array_diff($expected_exclusions, $actual_excluded_paths));
    }
    else {
      // The path of the database should not appear anywhere in the list of
      // excluded paths.
      $this->assertStringNotContainsString($db_path, serialize($actual_excluded_paths));
    }
  }

}
