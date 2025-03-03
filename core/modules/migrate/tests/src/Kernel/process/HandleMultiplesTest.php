<?php

declare(strict_types=1);

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
   *   The test migration definition.
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
        // Process pipeline for testing 'get' overriding a single.
        'get_from_single' => [
          // Returns a string.
          [
            'plugin' => 'get',
            'source' => 'scalar',
          ],
          // Ignore previous and return an array.
          [
            'plugin' => 'get',
            'source' => 'multiple',
          ],
        ],
        // Process pipeline for testing 'get' overriding an array.
        'get_from_multiple' => [
          // Returns an array.
          [
            'plugin' => 'get',
            'source' => 'multiple',
          ],
          // Ignore previous and return a string.
          [
            'plugin' => 'get',
            'source' => 'scalar',
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
   * @param array $source_data
   *   The source data.
   * @param array $expected_data
   *   The expected results.
   *
   * @dataProvider scalarAndMultipleValuesProviderSource
   */
  public function testScalarAndMultipleValues(array $source_data, array $expected_data): void {
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
   *   An array of test cases.
   */
  public static function scalarAndMultipleValuesProviderSource() {
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
          'get_from_single' => [
            'foo',
            'bar/baz',
          ],
          'get_from_multiple' => 'foo/bar',
        ],
      ],
    ];
  }

}
