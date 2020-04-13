<?php

namespace Drupal\Tests\migrate\Kernel;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the MigrateExecutable class.
 *
 * @group migrate
 */
class MigrateExecutableTest extends MigrateTestBase {

  protected static $modules = [
    'entity_test',
    'user',
  ];

  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests the MigrateExecutable class.
   */
  public function testMigrateExecutable() {
    $data_rows = [
      ['key' => '1', 'field1' => 'f1value1', 'field2' => 'f2value1'],
      ['key' => '2', 'field1' => 'f1value2', 'field2' => 'f2value2'],
    ];
    $ids = ['key' => ['type' => 'integer']];
    $definition = [
      'migration_tags' => ['Embedded data test'],
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => $data_rows,
        'ids' => $ids,
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:entity_test'],
    ];

    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
    $executable = new TestMigrateExecutable($migration);
    $this->assertEquals(MigrationInterface::RESULT_COMPLETED, $executable->import());
  }

}
