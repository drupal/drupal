<?php

declare(strict_types=1);

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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('entity_test');
  }

  /**
   * Tests the MigrateExecutable class.
   */
  public function testMigrateExecutable(): void {
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

    // Test the exception message when a process plugin throws a
    // MigrateSkipRowException. Change the definition to have one data row and a
    // process that will throw a MigrateSkipRowException on every row.
    $definition['source']['data_rows'] = [
      [
        'key' => '1',
        'field1' => 'f1value1',
      ],
    ];
    $definition['process'] = [
      'foo' => [
        'plugin' => 'skip_row_if_not_set',
        'index' => 'foo',
        'source' => 'field1',
        'message' => 'test message',
      ],
    ];

    $migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($definition);
    $executable = new TestMigrateExecutable($migration);
    $executable->import();
    $messages = iterator_to_array($migration->getIdMap()->getMessages());
    $this->assertCount(1, $messages);
    $expected = $migration->getPluginId() . ':foo: test message';
    $this->assertEquals($expected, $messages[0]->message);
  }

}
