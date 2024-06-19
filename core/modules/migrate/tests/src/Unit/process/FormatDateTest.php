<?php

declare(strict_types=1);

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
  public function testMigrateExceptionMissingFromFormat(): void {
    $configuration = [
      'from_format' => '',
      'to_format' => 'Y-m-d',
    ];

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Format date plugin is missing from_format configuration.');
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('01/05/1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests that missing configuration will throw an exception.
   */
  public function testMigrateExceptionMissingToFormat(): void {
    $configuration = [
      'from_format' => 'm/d/Y',
      'to_format' => '',
    ];

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Format date plugin is missing to_format configuration.');
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('01/05/1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests that date format mismatches will throw an exception.
   */
  public function testMigrateExceptionBadFormat(): void {
    $configuration = [
      'from_format' => 'm/d/Y',
      'to_format' => 'Y-m-d',
    ];

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("Format date plugin could not transform 'January 5, 1955' using the format 'm/d/Y'. Error: The date cannot be created from a format.");
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('January 5, 1955', $this->migrateExecutable, $this->row, 'field_date');
  }

  /**
   * Tests that an unexpected date value will throw an exception.
   */
  public function testMigrateExceptionUnexpectedValue(): void {
    $configuration = [
      'from_format' => 'm/d/Y',
      'to_format' => 'Y-m-d',
    ];

    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage("Format date plugin could not transform '01/05/55' using the format 'm/d/Y'. Error: The created date does not match the input value.");
    $this->plugin = new FormatDate($configuration, 'test_format_date', []);
    $this->plugin->transform('01/05/55', $this->migrateExecutable, $this->row, 'field_date');
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
  public function testTransform($configuration, $value, $expected): void {
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
  public static function datesDataProvider() {
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
          'to_format' => 'Y-m-d\TH:i:s e',
        ],
        'value' => '01/05/1955 10:43:22',
        'expected' => '1955-01-05T10:43:22 Australia/Sydney',
      ],
      'empty_values' => [
        'configuration' => [
          'from_format' => 'm/d/Y',
          'to_format' => 'Y-m-d',
        ],
        'value' => '',
        'expected' => '',
      ],
      'timezone_from_to' => [
        'configuration' => [
          'from_format' => 'Y-m-d H:i:s',
          'to_format' => 'Y-m-d H:i:s e',
          'from_timezone' => 'America/Managua',
          'to_timezone' => 'UTC',
        ],
        'value' => '2004-12-19 10:19:42',
        'expected' => '2004-12-19 16:19:42 UTC',
      ],
      'timezone_from' => [
        'configuration' => [
          'from_format' => 'Y-m-d h:i:s',
          'to_format' => 'Y-m-d h:i:s e',
          'from_timezone' => 'America/Managua',
        ],
        'value' => '2004-11-19 10:25:33',
        // Unit tests use Australia/Sydney timezone, so date value will be
        // converted from America/Managua to Australia/Sydney timezone.
        'expected' => '2004-11-20 03:25:33 Australia/Sydney',
      ],
      'timezone_to' => [
        'configuration' => [
          'from_format' => 'Y-m-d H:i:s',
          'to_format' => 'Y-m-d H:i:s e',
          'to_timezone' => 'America/Managua',
        ],
        'value' => '2004-12-19 10:19:42',
        // Unit tests use Australia/Sydney timezone, so date value will be
        // converted from Australia/Sydney to America/Managua timezone.
        'expected' => '2004-12-18 17:19:42 America/Managua',
      ],
      'integer_0' => [
        'configuration' => [
          'from_format' => 'U',
          'to_format' => 'Y-m-d',
        ],
        'value' => 0,
        'expected' => '1970-01-01',
      ],
      'string_0' => [
        'configuration' => [
          'from_format' => 'U',
          'to_format' => 'Y-m-d',
        ],
        'value' => '0',
        'expected' => '1970-01-01',
      ],
      'zeros' => [
        'configuration' => [
          'from_format' => 'Y-m-d H:i:s',
          'to_format' => 'Y-m-d H:i:s e',
          'settings' => ['validate_format' => FALSE],
        ],
        'value' => '0000-00-00 00:00:00',
        'expected' => '-0001-11-30 00:00:00 Australia/Sydney',
      ],
      'zeros_same_timezone' => [
        'configuration' => [
          'from_format' => 'Y-m-d H:i:s',
          'to_format' => 'Y-m-d H:i:s',
          'settings' => ['validate_format' => FALSE],
          'from_timezone' => 'UTC',
          'to_timezone' => 'UTC',
        ],
        'value' => '0000-00-00 00:00:00',
        'expected' => '-0001-11-30 00:00:00',
      ],
      'collected_date_attributes_day' => [
        'configuration' => [
          'from_format' => 'Y-m-d\TH:i:s',
          'to_format' => 'Y-m-d\TH:i:s',
        ],
        'value' => '2012-01-00T00:00:00',
        'expected' => '2012-01-01T00:00:00',
      ],
      'collected_date_attributes_month' => [
        'configuration' => [
          'from_format' => 'Y-m-d\TH:i:s',
          'to_format' => 'Y-m-d\TH:i:s',
        ],
        'value' => '2012-00-00T00:00:00',
        'expected' => '2012-01-01T00:00:00',
      ],
    ];
  }

}
