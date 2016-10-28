<?php

namespace Drupal\Tests\migrate\Kernel\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the extract process plugin.
 *
 * @group migrate
 */
class ExtractTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['migrate'];

  /**
   * Returns test migration definition.
   *
   * @return array
   */
  public function getDefinition() {
    return [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [],
        'ids' => [
          'id' => ['type' => 'string'],
        ],
      ],
      'process' => [
        'first' => [
          'plugin' => 'extract',
          'index' => [0],
          'source' => 'simple_array',
        ],
        'second' => [
          'plugin' => 'extract',
          'index' => [1],
          'source' => 'complex_array',
        ],
      ],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'migrate_test.settings',
      ],
    ];
  }

  /**
   * Tests multiple value handling.
   *
   * @dataProvider multipleValueProviderSource
   *
   * @param array $source_data
   * @param array $expected_data
   */
  public function testMultipleValueExplode(array $source_data, array $expected_data) {
    $definition = $this->getDefinition();
    $definition['source']['data_rows'] = [$source_data];

    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration, new MigrateMessage());
    $result = $executable->import();

    // Migration needs to succeed before further assertions are made.
    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $result);

    // Compare with expected data.
    $this->assertEquals($expected_data, \Drupal::config('migrate_test.settings')->get());
  }

  /**
   * Provides multiple source data for "extract" process plugin test.
   */
  public function multipleValueProviderSource() {
    $tests = [
      [
        'source_data' => [
          'id' => '1',
          'simple_array' => ['alpha', 'beta'],
          'complex_array' => [['alpha', 'beta'], ['psi', 'omega']],
        ],
        'expected_data' => [
          'first' => 'alpha',
          'second' => ['psi', 'omega'],
        ],
      ],
      [
        'source_data' => [
          'id' => '2',
          'simple_array' => ['one'],
          'complex_array' => [0, 1],
        ],
        'expected_data' => [
          'first' => 'one',
          'second' => 1,
        ],
      ],
    ];

    return $tests;
  }

}
