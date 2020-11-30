<?php

namespace Drupal\Tests\migrate\Kernel;

/**
 * Tests the migration plugin manager.
 *
 * @group migrate
 *
 * @coversDefaultClass \Drupal\migrate\Plugin\MigrationPluginManager
 */
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
  public function setUp(): void {
    parent::setUp();
    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
  }

  /**
   * Tests Migration::createInstancesByTag().
   *
   * @covers ::createInstancesByTag
   *
   * @dataProvider providerCreateInstanceByTag
   */
  public function testCreateInstancesByTag($tags, $expected) {
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
  public function providerCreateInstanceByTag() {
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
