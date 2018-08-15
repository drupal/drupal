<?php

namespace Drupal\Tests\migrate\Kernel\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests process pipelines with scalar and multiple values handling.
 *
 * @group migrate
 */
class HandleMultiplesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate'];

  /**
   * Provides the test migration definition.
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
        // Process pipeline for testing values from string to array to string.
        'first' => [
          // Expects a string and returns an array.
          [
            'plugin' => 'explode',
            'source' => 'scalar',
            'delimiter' => '/',
          ],
          // Expects an array and returns a string.
          [
            'plugin' => 'extract',
            'index' => [1],
          ],
          // Expects a string and returns a string.
          [
            'plugin' => 'callback',
            'callable' => 'strtoupper',
          ],
        ],
        // Process pipeline for testing values from array to string to array.
        'second' => [
          // Expects an array and returns a string.
          [
            'plugin' => 'extract',
            'source' => 'multiple',
            'index' => [1],
          ],
          // Expects a string and returns a string.
          [
            'plugin' => 'callback',
            'callable' => 'strtoupper',
          ],
          // Expects a string and returns an array.
          [
            'plugin' => 'explode',
            'delimiter' => '/',
          ],
        ],
      ],
      'destination' => [
        'plugin' => 'config',
        'config_name' => 'migrate_test.settings',
      ],
    ];
  }

  /**
   * Tests process pipelines with scalar and multiple values handling.
   *
   * @dataProvider scalarAndMultipleValuesProviderSource
   *
   * @param array $source_data
   * @param array $expected_data
   */
  public function testScalarAndMultipleValues(array $source_data, array $expected_data) {
    $definition = $this->getDefinition();
    $definition['source']['data_rows'] = [$source_data];

    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration);
    $result = $executable->import();

    // Migration needs to succeed before further assertions are made.
    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $result);

    // Compare with expected data.
    $this->assertEquals($expected_data, \Drupal::config('migrate_test.settings')->get());
  }

  /**
   * Provides the source data with scalar and multiple values.
   *
   * @return array
   */
  public function scalarAndMultipleValuesProviderSource() {
    return [
      [
        'source_data' => [
          'id' => '1',
          // Source value for the first pipeline.
          'scalar' => 'foo/bar',
          // Source value for the second pipeline.
          'multiple' => [
            'foo',
            'bar/baz',
          ],
        ],
        'expected_data' => [
          // Expected value from the first pipeline.
          'first' => 'BAR',
          // Expected value from the second pipeline.
          'second' => [
            'BAR',
            'BAZ',
          ],
        ],
      ],
    ];
  }

}
