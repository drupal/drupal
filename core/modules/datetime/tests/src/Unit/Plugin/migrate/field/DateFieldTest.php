<?php

namespace Drupal\Tests\datetime\Unit\Plugin\migrate\field;

use Drupal\datetime\Plugin\migrate\field\DateField;
use Drupal\migrate\MigrateException;
use Drupal\Tests\UnitTestCase;

// cspell:ignore todate

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
    $pipeline = [
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
    ];

    // If there is a todate then add a process for the end value.
    if (isset($data['field_definition']['data'])) {
      $tmp = is_string($data['field_definition']['data']) ? unserialize($data['field_definition']['data']) : '';
      $todate = $tmp['settings']['todate'] ?? NULL;
      if (!empty($todate)) {
        $pipeline['process']['end_value'] = [
          'plugin' => 'format_date',
          'from_format' => $from_format,
          'to_format' => $to_format,
          'source' => 'value2',
        ];
      }
    }
    $migration->expects($this->once())
      ->method('mergeProcessOfProperty')
      ->with('field_date', $pipeline)
      ->willReturn($migration);

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
      [
        [
          'type' => 'date',
          'field_definition' => [
            'data' => serialize([
              'settings' => [
                'granularity' => [
                  0 => 'year',
                  1 => 'month',
                ],
                'todate' => '',
              ],
            ]),
          ],
        ],
        'Y-m-d\TH:i:s',
        'Y-m-d',
      ],
      'datetime with a todate' => [
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
                'todate' => 'optional',
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

    $plugin->defineValueProcessPipeline($migration, 'field_date', ['type' => 'test']);
  }

}
