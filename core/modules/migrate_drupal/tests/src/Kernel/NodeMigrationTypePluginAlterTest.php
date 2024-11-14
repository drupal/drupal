<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\Tests\migrate_drupal\Traits\NodeMigrateTypeTestTrait;
use Drupal\migrate_drupal\Hook\MigrateDrupalHooks;

/**
 * Tests the assignment of the node migration type in migrations_plugin_alter.
 *
 * @group migrate_drupal
 */
class NodeMigrationTypePluginAlterTest extends MigrateTestBase {

  use NodeMigrateTypeTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_drupal', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setupDb();
  }

  /**
   * Tests the assignment of the node migration type.
   *
   * @param string $type
   *   The type of node migration, 'classic' or 'complete'.
   * @param array $migration_definitions
   *   An array of migration definitions.
   * @param array $expected
   *   The expected results.
   *
   * @dataProvider providerMigrationPluginAlter
   *
   * @throws \Exception
   */
  public function testMigrationPluginAlter($type, array $migration_definitions, array $expected): void {
    $this->makeNodeMigrateMapTable($type, '7');
    $migrateDrupalMigrationPluginsAlter = new MigrateDrupalHooks();
    $migrateDrupalMigrationPluginsAlter->migrationPluginsAlter($migration_definitions);
    $this->assertSame($expected, $migration_definitions);
  }

  /**
   * Data provider for testMigrationPluginAlter().
   */
  public static function providerMigrationPluginAlter() {
    $tests = [];

    $migrations = [
      // The 'system_site' migration is needed to get the legacy Drupal version.
      'system_site' => [
        'id' => 'system_site',
        'source' => [
          'plugin' => 'variable',
          'variables' => [
            'site_name',
            'site_mail',
          ],
          'source_module' => 'system',
        ],
        'process' => [],
      ],
      'no_dependencies_not_altered' => [
        'id' => 'no_dependencies_not_altered',
        'no_dependencies' => 'test',
        'process' => [
          'nid' => 'nid',
        ],
      ],
      'dependencies_altered_if_complete' => [
        'id' => 'test',
        'migration_dependencies' => [
          'required' => [
            'd7_node',
          ],
          'optional' => [
            'd7_node_translation',
          ],
        ],
      ],
      'dependencies_not_altered' => [
        'id' => 'd7_node',
        'migration_dependencies' => [
          'required' => [
            'd7_node',
          ],
          'optional' => [
            'd7_node_translation',
          ],
        ],
      ],
    ];

    // Test migrations are not altered when classic node migrations is in use.
    $tests[0]['type'] = NodeMigrateType::NODE_MIGRATE_TYPE_CLASSIC;
    $tests[0]['migration_definitions'] = $migrations;
    $tests[0]['expected'] = $tests[0]['migration_definitions'];

    // Test migrations are altered when complete node migrations is in use.
    $tests[1] = $tests[0];
    $tests[1]['type'] = NodeMigrateType::NODE_MIGRATE_TYPE_COMPLETE;
    $tests[1]['expected']['dependencies_altered_if_complete']['migration_dependencies'] = [
      'required' => [
        'd7_node_complete',
      ],
      'optional' => [
        'd7_node_complete',
      ],
    ];
    return $tests;
  }

  /**
   * Creates data in the source database.
   */
  protected function setupDb(): void {
    $this->sourceDatabase->schema()->createTable('system', [
      'fields' => [
        'name' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ],
        'type' => [
          'type' => 'varchar',
          'not null' => TRUE,
          'length' => '255',
          'default' => '',
        ],
        'status' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '0',
        ],
        'schema_version' => [
          'type' => 'int',
          'not null' => TRUE,
          'size' => 'normal',
          'default' => '-1',
        ],
      ],
    ]);
    $this->sourceDatabase->insert('system')
      ->fields([
        'name',
        'type',
        'status',
        'schema_version',
      ])
      ->values([
        'name' => 'system',
        'type' => 'module',
        'status' => '1',
        'schema_version' => '7001',
      ])
      ->execute();
  }

}
