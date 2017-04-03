<?php

namespace Drupal\Tests\migrate\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\process\FormatDate;

/**
 * Tests the format date process plugin.
 *
 * @group migrate
 *
 * @coversDefaultClass Drupal\migrate\Plugin\migrate\process\FormatDate
 */
class FormatDateTest extends MigrateProcessTestCase {

  /**
   * Tests that missing configuration will throw an exception.
   */
  public function testMigrateExceptionMissingFromFormat() {
    $configuration = [
      'from_format' => '',
      'to_format' => 'Y-m-d',
    ];

    $this->setExpectedException(MigrateException::class, 'Format date plugin is missing from_format configuration.');
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('01/05/1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests that missing configuration will throw an exception.
   */
  public function testMigrateExceptionMissingToFormat() {
    $configuration = [
      'from_format' => 'm/d/Y',
      'to_format' => '',
    ];

    $this->setExpectedException(MigrateException::class, 'Format date plugin is missing to_format configuration.');
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('01/05/1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests that date format mismatches will throw an exception.
   */
  public function testMigrateExceptionBadFormat() {
    $configuration = [
      'from_format' => 'm/d/Y',
      'to_format' => 'Y-m-d',
    ];

    $this->setExpectedException(MigrateException::class, 'Format date plugin could not transform "January 5, 1955" using the format "m/d/Y". Error: The date cannot be created from a format.');
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('January 5, 1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests transformation.
   *
   * @covers ::transform
   *
   * @dataProvider datesDataProvider
   *
   * @param $configuration
   *   The configuration of the migration process plugin.
   * @param $value
   *   The source value for the migration process plugin.
   * @param $expected
   *   The expected value of the migration process plugin.
   */
  public function testTransform($configuration, $value, $expected) {
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $actual = $this->plugin->transform($value, $this->migrateExecutable, $this->row, 'field_date');

    $this->assertEquals($expected, $actual);
  }

  /**
   * Data provider of test dates.
   *
   * @return array
   *   Array of date formats and actual/expected values.
   */
  public function datesDataProvider() {
    return [
      'datetime_date' => [
        'configuration' => [
          'from_format' => 'm/d/Y',
          'to_format' => 'Y-m-d',
        ],
        'value' => '01/05/1955',
        'expected' => '1955-01-05',
      ],
      'datetime_datetime' => [
        'configuration' => [
          'from_format' => 'm/d/Y H:i:s',
          'to_format' => 'Y-m-d\TH:i:s',
        ],
        'value' => '01/05/1955 10:43:22',
        'expected' => '1955-01-05T10:43:22',
      ],
      'empty_values' => [
        'configuration' => [
          'from_format' => 'm/d/Y',
          'to_format' => 'Y-m-d',
        ],
        'value' => '',
        'expected' => '',
      ],
      'timezone' => [
        'configuration' => [
          'from_format' => 'Y-m-d\TH:i:sO',
          'to_format' => 'Y-m-d\TH:i:s',
          'timezone' => 'America/Managua',
        ],
        'value' => '2004-12-19T10:19:42-0600',
        'expected' => '2004-12-19T10:19:42',
      ],
    ];
  }

}
