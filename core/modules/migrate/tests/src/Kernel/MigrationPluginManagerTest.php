<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\Plugin\MigrationPluginManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the migration plugin manager.
 */
#[CoversClass(MigrationPluginManager::class)]
#[Group('migrate')]
#[RunTestsInSeparateProcesses]
class MigrationPluginManagerTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_tag_test'];

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
  }

  /**
   * Tests Migration::createInstancesByTag().
   */
  #[DataProvider('providerCreateInstanceByTag')]
  public function testCreateInstancesByTag($tags, $expected): void {
    // The test module includes a migration that does not use the migration_tags
    // property. It is there to confirm that it is not included in the results.
    // We create it to ensure it is a valid migration.
    $migration = $this->migrationPluginManager->createInstances(['tag_test_no_tag']);
    $this->assertArrayHasKey('tag_test_no_tag', $migration);
    $migrations = $this->migrationPluginManager->createInstancesByTag($tags);
    $actual = array_keys($migrations);
    $this->assertSame($expected, $actual);
  }

  /**
   * Data provider for testCreateInstancesByTag.
   */
  public static function providerCreateInstanceByTag() {
    return [
      'get test' => [
        'test',
        ['tag_test_0', 'tag_test_1'],
      ],
      'get tag_test_1' => [
        'tag_test_1',
        ['tag_test_1'],
      ],
      'get no tags' => [
        '',
        [],
      ],
    ];

  }

}
