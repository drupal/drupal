<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\Plugin\id_map;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Exception\SchemaTableColumnSizeTooLargeException;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\Tests\migrate\Unit\TestSqlIdMap;
use Drupal\migrate\MigrateException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests that the migrate map table is created.
 *
 * @group migrate
 */
class SqlTest extends MigrateTestBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A mock event dispatcher.
   *
   * @var object|\Prophecy\Prophecy\ProphecySubjectInterface|\Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Definition of a test migration.
   *
   * @var array
   */
  protected $migrationDefinition;

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
    $this->database = \Drupal::database();
    $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class)
      ->reveal();
    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');

    $this->migrationDefinition = [
      'id' => 'test',
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'alpha' => '1',
            'bravo' => '2',
            'charlie' => '3',
            'delta' => '4',
            'echo' => '5',
          ],
        ],
        'ids' => [],
      ],
      'process' => [],
      'destination' => [
        'plugin' => 'null',
      ],
    ];
  }

  /**
   * Tests that ensureTables creates the migrate map table.
   *
   * @dataProvider providerTestEnsureTables
   */
  public function testEnsureTables($ids): void {
    $this->migrationDefinition['source']['ids'] = $ids;
    $migration = $this->migrationPluginManager->createStubMigration($this->migrationDefinition);

    $map = new TestSqlIdMap($this->database, [], 'test', [], $migration, $this->eventDispatcher, $this->migrationPluginManager);
    $map->ensureTables();

    // Checks that the map table was created.
    $exists = $this->database->schema()->tableExists('migrate_map_test');
    $this->assertTrue($exists);
  }

  /**
   * Provides data for testEnsureTables.
   */
  public static function providerTestEnsureTables() {
    return [
      'no ids' => [
        [],
      ],
      'one id' => [
        [
          'alpha' => [
            'type' => 'string',
          ],
        ],
      ],
      'too many' => [
        [
          'alpha' => [
            'type' => 'string',
          ],
          'bravo' => [
            'type' => 'string',
          ],
          'charlie' => [
            'type' => 'string',
          ],
          'delta' => [
            'type' => 'string',
          ],
          'echo ' => [
            'type' => 'string',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests exception is thrown in ensureTables fails.
   *
   * @dataProvider providerTestFailEnsureTables
   */
  public function testFailEnsureTables($ids): void {
    // This just tests mysql, as other PDO integrations allow longer indexes.
    if (Database::getConnection()->databaseType() !== 'mysql') {
      $this->markTestSkipped("This test only runs for MySQL");
    }

    $this->migrationDefinition['source']['ids'] = $ids;
    $migration = $this->container
      ->get('plugin.manager.migration')
      ->createStubMigration($this->migrationDefinition);

    // Use local id map plugin to force an error.
    $map = new SqlIdMapTest($this->database, [], 'test', [], $migration, $this->eventDispatcher, $this->migrationPluginManager);

    $this->expectException(SchemaTableColumnSizeTooLargeException::class);
    $map->ensureTables();
  }

  /**
   * Provides data for testFailEnsureTables.
   */
  public static function providerTestFailEnsureTables() {
    return [
      'one id' => [
        [
          'alpha' => [
            'type' => 'string',
          ],
        ],
      ],
    ];
  }

}

/**
 * Defines a test SQL ID map for use in tests.
 */
class SqlIdMapTest extends TestSqlIdMap implements \Iterator {

  /**
   * {@inheritdoc}
   */
  protected function getFieldSchema(array $id_definition) {
    if (!isset($id_definition['type'])) {
      return [];
    }
    switch ($id_definition['type']) {
      case 'integer':
        return [
          'type' => 'int',
          'not null' => TRUE,
        ];

      case 'string':
        return [
          'type' => 'varchar',
          'length' => 65536,
          'not null' => FALSE,
        ];

      default:
        throw new MigrateException($id_definition['type'] . ' not supported');
    }
  }

}
