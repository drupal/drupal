<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate\Kernel\process;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests process pipelines when a sub_process skips a row or process.
 *
 * @group migrate
 */
class SubProcessWithSkipTest extends KernelTestBase {

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
        'data_rows' => [
          [
            'id' => 'skip_test',
            'my_array_of_arrays' => [
              [
                'key_1' => 'foo',
                'key_2' => 'bar',
              ],
              [
                'key_1' => NULL,
                'key_2' => 'baz',
              ],
            ],
          ],
        ],
        'ids' => [
          'id' => ['type' => 'string'],
        ],
      ],
      'process' => [
        'first' => [
          'plugin' => 'default_value',
          'default_value' => 'something outside of sub_process',
        ],
        'second' => [
          'plugin' => 'sub_process',
          'source' => 'my_array_of_arrays',
          'process' => [
            'prop_1' => [
              [
                'plugin' => 'skip_on_empty',
                'source' => 'key_1',
              ],
              // We put a process after skip_on_empty to better test skipping
              // a process.
              [
                'plugin' => 'get',
                'source' => 'key_2',
              ],
            ],
            'prop_2' => 'key_2',
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
   * Test use of skip_on_empty within sub_process.
   *
   * @param string $method
   *   The method to use with skip_on_empty (row or process).
   * @param array $expected_data
   *   The expected result of the migration.
   *
   * @dataProvider providerTestSubProcessSkip
   */
  public function testSubProcessSkip(string $method, array $expected_data): void {
    $definition = $this->getDefinition();
    $definition['process']['second']['process']['prop_1'][0]['method'] = $method;

    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration);
    $result = $executable->import();

    // Migration needs to succeed before further assertions are made.
    $this->assertSame(MigrationInterface::RESULT_COMPLETED, $result);

    // Compare with expected data.
    $this->assertEquals($expected_data, \Drupal::config('migrate_test.settings')->get());
  }

  /**
   * Data provider for testNotFoundSubProcess().
   *
   * @return array
   *   The data for the testNotFoundSubProcess() test.
   */
  public static function providerTestSubProcessSkip(): array {
    return [
      'skip row' => [
        'method' => 'row',
        'expected_data' => [
          'first' => 'something outside of sub_process',
          'second' => [
            [
              'prop_1' => 'bar',
              'prop_2' => 'bar',
            ],
          ],
        ],
      ],
      'skip process' => [
        'method' => 'process',
        'expected_data' => [
          'first' => 'something outside of sub_process',
          'second' => [
            [
              'prop_1' => 'bar',
              'prop_2' => 'bar',
            ],
            [
              'prop_2' => 'baz',
            ],
          ],
        ],
      ],
    ];
  }

}
