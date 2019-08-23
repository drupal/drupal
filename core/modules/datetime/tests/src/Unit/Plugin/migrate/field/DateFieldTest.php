<?php

namespace Drupal\Tests\datetime\Unit\Plugin\migrate\field;

use Drupal\datetime\Plugin\migrate\field\DateField;
use Drupal\migrate\MigrateException;
use Drupal\Tests\UnitTestCase;

/**
 * Provides unit tests for the DateField Plugin.
 *
 * @coversDefaultClass \Drupal\datetime\Plugin\migrate\field\DateField
 *
 * @group migrate
 */
class DateFieldTest extends UnitTestCase {

  /**
   * Tests defineValueProcessPipeline.
   *
   * @covers ::defineValueProcessPipeline
   *
   * @dataProvider providerTestDefineValueProcessPipeline
   */
  public function testDefineValueProcessPipeline($data, $from_format, $to_format) {
    $migration = $this->createMock('Drupal\migrate\Plugin\MigrationInterface');
    $migration->expects($this->once())
      ->method('mergeProcessOfProperty')
      ->with('field_date', [
        'plugin' => 'sub_process',
        'source' => 'field_date',
        'process' => [
          'value' => [
            'plugin' => 'format_date',
            'from_format' => $from_format,
            'to_format' => $to_format,
            'source' => 'value',
          ],
        ],
      ])
      ->will($this->returnValue($migration));

    $plugin = new DateField([], '', []);
    $plugin->defineValueProcessPipeline($migration, 'field_date', $data);
  }

  /**
   * Provides data for testDefineValueProcessPipeline().
   */
  public function providerTestDefineValueProcessPipeline() {
    return [
      [['type' => 'date'], 'Y-m-d\TH:i:s', 'Y-m-d\TH:i:s'],
      [['type' => 'datestamp'], 'U', 'U'],
      [['type' => 'datetime'], 'Y-m-d H:i:s', 'Y-m-d\TH:i:s'],
      [
        [
          'type' => 'datetime',
          'field_definition' => [
            'data' => serialize([
              'settings' => [
                'granularity' => [
                  'hour' => 0,
                  'minute' => 0,
                  'second' => 0,
                ],
              ],
            ]),
          ],
        ],
        'Y-m-d H:i:s',
        'Y-m-d',
      ],
    ];
  }

  /**
   * Tests invalid date types throw an exception.
   *
   * @covers ::defineValueProcessPipeline
   */
  public function testDefineValueProcessPipelineException() {
    $migration = $this->createMock('Drupal\migrate\Plugin\MigrationInterface');

    $plugin = new DateField([], '', []);

    $this->expectException(MigrateException::class);
    $plugin->defineValueProcessPipeline($migration, 'field_date', ['type' => 'totoro']);
  }

}
