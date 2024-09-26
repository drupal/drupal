<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Datetime;

use Drupal\Component\Datetime\DateTimePlus;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Datetime\DateTimePlus
 * @group Datetime
 */
class DateTimePlusTest extends TestCase {

  /**
   * Tests creating dates from string and array input.
   *
   * @param mixed $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $expected
   *   Expected output from DateTimePlus::format().
   *
   * @dataProvider providerTestDates
   */
  public function testDates($input, $timezone, $expected): void {
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');

    if (is_array($input)) {
      $input = var_export($input, TRUE);
    }
    $this->assertEquals($expected, $value, sprintf("Test new DateTimePlus(%s, %s): should be %s, found %s.", $input, $timezone, $expected, $value));
  }

  /**
   * Tests creating dates from string and array input.
   *
   * @param mixed $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $expected
   *   Expected output from DateTimePlus::format().
   *
   * @dataProvider providerTestDateArrays
   */
  public function testDateArrays($input, $timezone, $expected): void {
    $date = DateTimePlus::createFromArray($input, $timezone);
    $value = $date->format('c');

    if (is_array($input)) {
      $input = var_export($input, TRUE);
    }
    $this->assertEquals($expected, $value, sprintf("Test new DateTimePlus(%s, %s): should be %s, found %s.", $input, $timezone, $expected, $value));
  }

  /**
   * Tests date diffs.
   *
   * @param mixed $input1
   *   A DateTimePlus object.
   * @param mixed $input2
   *   Date argument for DateTimePlus::diff method.
   * @param bool $absolute
   *   Absolute flag for DateTimePlus::diff method.
   * @param \DateInterval $expected
   *   The expected result of the DateTimePlus::diff operation.
   *
   * @dataProvider providerTestDateDiff
   */
  public function testDateDiff($input1, $input2, $absolute, \DateInterval $expected): void {
    $interval = $input1->diff($input2, $absolute);
    $this->assertEquals($interval, $expected);
  }

  /**
   * Tests date diff exception caused by invalid input.
   *
   * @param mixed $input1
   *   A DateTimePlus object.
   * @param mixed $input2
   *   Date argument for DateTimePlus::diff method.
   * @param bool $absolute
   *   Absolute flag for DateTimePlus::diff method.
   *
   * @dataProvider providerTestInvalidDateDiff
   */
  public function testInvalidDateDiff($input1, $input2, $absolute): void {
    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('Method Drupal\Component\Datetime\DateTimePlus::diff expects parameter 1 to be a \DateTime or \Drupal\Component\Datetime\DateTimePlus object');
    $interval = $input1->diff($input2, $absolute);
  }

  /**
   * Tests creating dates from invalid array input.
   *
   * @param mixed $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $class
   *   The Exception subclass to expect to be thrown.
   *
   * @dataProvider providerTestInvalidDateArrays
   */
  public function testInvalidDateArrays($input, $timezone, $class): void {
    $this->expectException($class);
    $this->assertInstanceOf(
      '\Drupal\Component\DateTimePlus',
      DateTimePlus::createFromArray($input, $timezone)
    );
  }

  /**
   * Tests DateTimePlus::checkArray().
   *
   * @param array $array
   *   Input argument for DateTimePlus::checkArray().
   * @param bool $expected
   *   The expected result of DateTimePlus::checkArray().
   *
   * @dataProvider providerTestCheckArray
   */
  public function testCheckArray(array $array, $expected): void {
    $this->assertSame(
      $expected,
      DateTimePlus::checkArray($array)
    );
  }

  /**
   * Tests creating dates from timestamps, and manipulating timezones.
   *
   * @param int $input
   *   Input argument for DateTimePlus::createFromTimestamp().
   * @param array $initial
   *   An array containing:
   *   - 'timezone_initial' - Timezone argument for DateTimePlus.
   *   - 'format_initial' - Format argument for DateTimePlus.
   *   - 'expected_initial_date' - Expected output from DateTimePlus::format().
   *   - 'expected_initial_timezone' - Expected output from
   *      DateTimePlus::getTimeZone()::getName().
   *   - 'expected_initial_offset' - Expected output from DateTimePlus::getOffset().
   * @param array $transform
   *   An array containing:
   *   - 'timezone_transform' - Argument to transform date to another timezone via
   *     DateTimePlus::setTimezone().
   *   - 'format_transform' - Format argument to use when transforming date to
   *     another timezone.
   *   - 'expected_transform_date' - Expected output from DateTimePlus::format(),
   *     after timezone transform.
   *   - 'expected_transform_timezone' - Expected output from
   *     DateTimePlus::getTimeZone()::getName(), after timezone transform.
   *   - 'expected_transform_offset' - Expected output from
   *      DateTimePlus::getOffset(), after timezone transform.
   *
   * @dataProvider providerTestTimestamp
   */
  public function testTimestamp($input, array $initial, array $transform): void {
    // Initialize a new date object.
    $date = DateTimePlus::createFromTimestamp($input, $initial['timezone']);
    $this->assertDateTimestamp($date, $input, $initial, $transform);
  }

  /**
   * Tests creating dates from datetime strings.
   *
   * @param string $input
   *   Input argument for DateTimePlus().
   * @param array $initial
   *   @see testTimestamp()
   * @param array $transform
   *   @see testTimestamp()
   *
   * @dataProvider providerTestDateTimestamp
   */
  public function testDateTimestamp($input, array $initial, array $transform): void {
    // Initialize a new date object.
    $date = new DateTimePlus($input, $initial['timezone']);
    $this->assertDateTimestamp($date, $input, $initial, $transform);
  }

  /**
   * Asserts a DateTimePlus value.
   *
   * @param \Drupal\Component\Datetime\DateTimePlus $date
   *   DateTimePlus to test.
   * @param string|int $input
   *   The original input passed to the test method.
   * @param array $initial
   *   @see testTimestamp()
   * @param array $transform
   *   @see testTimestamp()
   *
   * @internal
   */
  public function assertDateTimestamp(DateTimePlus $date, string|int $input, array $initial, array $transform): void {
    // Check format.
    $value = $date->format($initial['format']);
    $this->assertEquals($initial['expected_date'], $value, sprintf("Test new DateTimePlus(%s, %s): should be %s, found %s.", $input, $initial['timezone'], $initial['expected_date'], $value));

    // Check timezone name.
    $value = $date->getTimeZone()->getName();
    $this->assertEquals($initial['expected_timezone'], $value, sprintf("The current timezone is %s: should be %s.", $value, $initial['expected_timezone']));

    // Check offset.
    $value = $date->getOffset();
    $this->assertEquals($initial['expected_offset'], $value, sprintf("The current offset is %s: should be %s.", $value, $initial['expected_offset']));

    // Transform the date to another timezone.
    $date->setTimezone(new \DateTimeZone($transform['timezone']));

    // Check transformed format.
    $value = $date->format($transform['format']);
    $this->assertEquals($transform['expected_date'], $value, sprintf("Test \$date->setTimezone(new \\DateTimeZone(%s)): should be %s, found %s.", $transform['timezone'], $transform['expected_date'], $value));

    // Check transformed timezone.
    $value = $date->getTimeZone()->getName();
    $this->assertEquals($transform['expected_timezone'], $value, sprintf("The current timezone should be %s, found %s.", $transform['expected_timezone'], $value));

    // Check transformed offset.
    $value = $date->getOffset();
    $this->assertEquals($transform['expected_offset'], $value, sprintf("The current offset should be %s, found %s.", $transform['expected_offset'], $value));
  }

  /**
   * Tests creating dates from format strings.
   *
   * @param string $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $format
   *   PHP date() type format for parsing the input.
   * @param string $format_date
   *   Format argument for DateTimePlus::format().
   * @param string $expected
   *   Expected output from DateTimePlus::format().
   *
   * @dataProvider providerTestDateFormat
   */
  public function testDateFormat($input, $timezone, $format, $format_date, $expected): void {
    $date = DateTimePlus::createFromFormat($format, $input, $timezone);
    $value = $date->format($format_date);
    $this->assertEquals($expected, $value, sprintf("Test new DateTimePlus(%s, %s, %s): should be %s, found %s.", $input, $timezone, $format, $expected, $value));
  }

  /**
   * Tests invalid date handling.
   *
   * @param mixed $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $format
   *   Format argument for DateTimePlus.
   * @param string $message
   *   Message to print if no errors are thrown by the invalid dates.
   * @param string $class
   *   The Exception subclass to expect to be thrown.
   *
   * @dataProvider providerTestInvalidDates
   */
  public function testInvalidDates($input, $timezone, $format, $message, $class): void {
    $this->expectException($class);
    DateTimePlus::createFromFormat($format, $input, $timezone);
  }

  /**
   * Tests that DrupalDateTime can detect the right timezone to use.
   *
   * When specified or not.
   *
   * @param mixed $input
   *   Input argument for DateTimePlus.
   * @param mixed $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $expected_timezone
   *   Expected timezone returned from DateTimePlus::getTimezone::getName().
   * @param string $message
   *   Message to print on test failure.
   *
   * @dataProvider providerTestDateTimezone
   */
  public function testDateTimezone($input, $timezone, $expected_timezone, $message): void {
    $date = new DateTimePlus($input, $timezone);
    $timezone = $date->getTimezone()->getName();
    $this->assertEquals($timezone, $expected_timezone, $message);
  }

  /**
   * Tests that DrupalDateTime can detect the correct timezone to use.
   *
   * But only when the DrupalDateTime is constructed from a datetime object.
   */
  public function testDateTimezoneWithDateTimeObject(): void {
    // Create a date object with another date object.
    $input = new \DateTime('now', new \DateTimeZone('Pacific/Midway'));
    $timezone = NULL;
    $expected_timezone = 'Pacific/Midway';
    $message = 'DateTimePlus uses the specified timezone if provided.';

    $date = DateTimePlus::createFromDateTime($input, $timezone);
    $timezone = $date->getTimezone()->getName();
    $this->assertEquals($timezone, $expected_timezone, $message);
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DateTimePlusTest::testDates().
   *
   * @see DateTimePlusTest::testDates()
   */
  public static function providerTestDates() {
    $dates = [
      // String input.
      // Create date object from datetime string.
      ['2009-03-07 10:30', 'America/Chicago', '2009-03-07T10:30:00-06:00'],
      // Same during daylight savings time.
      ['2009-06-07 10:30', 'America/Chicago', '2009-06-07T10:30:00-05:00'],
      // Create date object from date string.
      ['2009-03-07', 'America/Chicago', '2009-03-07T00:00:00-06:00'],
      // Same during daylight savings time.
      ['2009-06-07', 'America/Chicago', '2009-06-07T00:00:00-05:00'],
      // Create date object from date string.
      ['2009-03-07 10:30', 'Australia/Canberra', '2009-03-07T10:30:00+11:00'],
      // Same during daylight savings time.
      ['2009-06-07 10:30', 'Australia/Canberra', '2009-06-07T10:30:00+10:00'],
    ];

    // On 32-bit systems, timestamps are limited to 1901-2038.
    if (PHP_INT_SIZE > 4) {
      // Create a date object in the distant past.
      // @see https://www.drupal.org/node/2795489#comment-12127088
      // Note that this date is after the United States standardized its
      // timezones.
      $dates[] = ['1883-11-19 10:30', 'America/Chicago', '1883-11-19T10:30:00-06:00'];
      // Create a date object in the far future.
      $dates[] = ['2345-01-02 02:04', 'UTC', '2345-01-02T02:04:00+00:00'];
    }

    return $dates;
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DateTimePlusTest::testDates().
   *
   * @see DateTimePlusTest::testDates()
   */
  public static function providerTestDateArrays() {
    $dates = [
      // Array input.
      // Create date object from date array, date only.
      [['year' => 2010, 'month' => 2, 'day' => 28], 'America/Chicago', '2010-02-28T00:00:00-06:00'],
      // Create date object from date array with hour.
      [['year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10], 'America/Chicago', '2010-02-28T10:00:00-06:00'],
      // Create date object from date array, date only.
      [['year' => 2010, 'month' => 2, 'day' => 28], 'Europe/Berlin', '2010-02-28T00:00:00+01:00'],
      // Create date object from date array with hour.
      [['year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10], 'Europe/Berlin', '2010-02-28T10:00:00+01:00'],
    ];

    // On 32-bit systems, timestamps are limited to 1901-2038.
    if (PHP_INT_SIZE > 4) {
      // Create a date object in the distant past.
      // @see https://www.drupal.org/node/2795489#comment-12127088
      // Note that this date is after the United States standardized its
      // timezones.
      $dates[] = [['year' => 1883, 'month' => 11, 'day' => 19], 'America/Chicago', '1883-11-19T00:00:00-06:00'];
      // Create a date object in the far future.
      $dates[] = [['year' => 2345, 'month' => 1, 'day' => 2], 'UTC', '2345-01-02T00:00:00+00:00'];
    }

    return $dates;
  }

  /**
   * Provides data for testDateFormats.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'input' - Input to DateTimePlus.
   *   - 'timezone' - Timezone for DateTimePlus.
   *   - 'format' - Date format for DateTimePlus.
   *   - 'format_date' - Date format for use in $date->format() method.
   *   - 'expected' - The expected return from DateTimePlus.
   *
   * @see testDateFormats()
   */
  public static function providerTestDateFormat() {
    return [
      // Create a year-only date.
      ['2009', NULL, 'Y', 'Y', '2009'],
      // Create a month and year-only date.
      ['2009-10', NULL, 'Y-m', 'Y-m', '2009-10'],
      // Create a time-only date.
      ['T10:30:00', NULL, '\TH:i:s', 'H:i:s', '10:30:00'],
      // Create a time-only date.
      ['10:30:00', NULL, 'H:i:s', 'H:i:s', '10:30:00'],
    ];
  }

  /**
   * Provides data for testInvalidDates.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'input' - Input for DateTimePlus.
   *   - 'timezone' - Timezone for DateTimePlus.
   *   - 'format' - Format for DateTimePlus.
   *   - 'message' - Message to display on failure.
   *
   * @see testInvalidDates
   */
  public static function providerTestInvalidDates() {
    return [
      // Test for invalid month names when we are using a short version
      // of the month.
      ['23 abc 2012', NULL, 'd M Y', "23 abc 2012 contains an invalid month name and did not produce errors.", \InvalidArgumentException::class],
      // Test for invalid hour.
      ['0000-00-00T45:30:00', NULL, 'Y-m-d\TH:i:s', "0000-00-00T45:30:00 contains an invalid hour and did not produce errors.", \UnexpectedValueException::class],
      // Test for invalid day.
      ['0000-00-99T05:30:00', NULL, 'Y-m-d\TH:i:s', "0000-00-99T05:30:00 contains an invalid day and did not produce errors.", \UnexpectedValueException::class],
      // Test for invalid month.
      ['0000-75-00T15:30:00', NULL, 'Y-m-d\TH:i:s', "0000-75-00T15:30:00 contains an invalid month and did not produce errors.", \UnexpectedValueException::class],
      // Test for invalid year.
      ['11-08-01T15:30:00', NULL, 'Y-m-d\TH:i:s', "11-08-01T15:30:00 contains an invalid year and did not produce errors.", \UnexpectedValueException::class],

    ];
  }

  /**
   * Data provider for testInvalidDateArrays.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'input' - Input for DateTimePlus.
   *   - 'timezone' - Timezone for DateTimePlus.
   *
   * @see testInvalidDateArrays
   */
  public static function providerTestInvalidDateArrays() {
    return [
      // One year larger than the documented upper limit of checkdate().
      [['year' => 32768, 'month' => 1, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0], 'America/Chicago', \InvalidArgumentException::class],
      // One year smaller than the documented lower limit of checkdate().
      [['year' => 0, 'month' => 1, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0], 'America/Chicago', \InvalidArgumentException::class],
      // Test for invalid month from date array.
      [['year' => 2010, 'month' => 27, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0], 'America/Chicago', \InvalidArgumentException::class],
      // Test for invalid hour from date array.
      [['year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 80, 'minute' => 0, 'second' => 0], 'America/Chicago', \InvalidArgumentException::class],
      // Test for invalid minute from date array.
      [['year' => 2010, 'month' => 7, 'day' => 8, 'hour' => 8, 'minute' => 88, 'second' => 0], 'America/Chicago', \InvalidArgumentException::class],
      // Regression test for https://www.drupal.org/node/2084455.
      [['hour' => 59, 'minute' => 1, 'second' => 1], 'America/Chicago', \InvalidArgumentException::class],
    ];
  }

  /**
   * Data provider for testCheckArray.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'array' - Input for DateTimePlus::checkArray().
   *   - 'expected' - Expected output for  DateTimePlus::checkArray().
   *
   * @see testCheckArray
   */
  public static function providerTestCheckArray() {
    return [
      'Date array, date only' => [['year' => 2010, 'month' => 2, 'day' => 28], TRUE],
      'Date array with hour' => [['year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10], TRUE],
      'One year larger than the documented upper limit of checkdate()' => [['year' => 32768, 'month' => 1, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0], FALSE],
      'One year smaller than the documented lower limit of checkdate()' => [['year' => 0, 'month' => 1, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0], FALSE],
      'Invalid month from date array' => [['year' => 2010, 'month' => 27, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0], FALSE],
      'Invalid hour from date array' => [['year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 80, 'minute' => 0, 'second' => 0], FALSE],
      'Invalid minute from date array.' => [['year' => 2010, 'month' => 7, 'day' => 8, 'hour' => 8, 'minute' => 88, 'second' => 0], FALSE],
      'Missing day' => [['year' => 2059, 'month' => 1, 'second' => 1], FALSE],
      'Zero day' => [['year' => 2059, 'month' => 1, 'day' => 0], FALSE],
    ];
  }

  /**
   * Provides data for testDateTimezone.
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'date' - Date string or object for DateTimePlus.
   *   - 'timezone' - Timezone string for DateTimePlus.
   *   - 'expected' - Expected return from DateTimePlus::getTimezone()::getName().
   *   - 'message' - Message to display on test failure.
   *
   * @see testDateTimezone
   */
  public static function providerTestDateTimezone() {
    // Use a common date for most of the tests.
    $date_string = '2007-01-31 21:00:00';

    // Detect the system timezone.
    $system_timezone = date_default_timezone_get();

    return [
      // Create a date object with an unspecified timezone, which should
      // end up using the system timezone.
      [$date_string, NULL, $system_timezone, 'DateTimePlus uses the system timezone when there is no site timezone.'],
      // Create a date object with a specified timezone name.
      [$date_string, 'America/Yellowknife', 'America/Yellowknife', 'DateTimePlus uses the specified timezone if provided.'],
      // Create a date object with a timezone object.
      [$date_string, new \DateTimeZone('Australia/Canberra'), 'Australia/Canberra', 'DateTimePlus uses the specified timezone if provided.'],
      // Create a date object with another date object.
      [new DateTimePlus('now', 'Pacific/Midway'), NULL, 'Pacific/Midway', 'DateTimePlus uses the specified timezone if provided.'],
    ];
  }

  /**
   * Provides data for testTimestamp.
   *
   * @return array
   *   An array of arrays, each containing the arguments required for
   *   self::testTimestamp().
   *
   * @see testTimestamp()
   */
  public static function providerTestTimestamp() {
    return [
      // Create date object from a unix timestamp and display it in
      // local time.
      [
        'input' => 0,
        'initial' => [
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ],
        'transform' => [
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ],
      ],
      // Create a date using the timestamp of zero, then display its
      // value both in UTC and the local timezone.
      [
        'input' => 0,
        'initial' => [
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ],
        'transform' => [
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ],
      ],
    ];
  }

  /**
   * Provides data for testDateTimestamp.
   *
   * @return array
   *   An array of arrays, each containing the arguments required for
   *   self::testDateTimestamp().
   *
   * @see testDateTimestamp()
   */
  public static function providerTestDateTimestamp() {
    return [
      // Create date object from datetime string in UTC, and convert
      // it to a local date.
      [
        'input' => '1970-01-01 00:00:00',
        'initial' => [
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ],
        'transform' => [
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ],
      ],
      // Convert the local time to UTC using string input.
      [
        'input' => '1969-12-31 16:00:00',
        'initial' => [
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ],
        'transform' => [
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ],
      ],
      // Convert the local time to UTC using string input.
      [
        'input' => '1969-12-31 16:00:00',
        'initial' => [
          'timezone' => 'Europe/Warsaw',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00+01:00',
          'expected_timezone' => 'Europe/Warsaw',
          'expected_offset' => '+3600',
        ],
        'transform' => [
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1969-12-31T15:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ],
      ],
    ];
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DateTimePlusTest::testDateDiff().
   *
   * @see DateTimePlusTest::testDateDiff()
   */
  public static function providerTestDateDiff() {

    $empty_interval = new \DateInterval('PT0S');

    $positive_19_hours = new \DateInterval('PT19H');

    $positive_18_hours = new \DateInterval('PT18H');

    $positive_1_hour = new \DateInterval('PT1H');

    $negative_1_hour = new \DateInterval('PT1H');
    $negative_1_hour->invert = 1;

    return [
      // There should be a 19 hour time interval between
      // new years in Sydney and new years in LA in year 2000.
      [
        'input2' => DateTimePlus::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00', new \DateTimeZone('Australia/Sydney')),
        'input1' => DateTimePlus::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00', new \DateTimeZone('America/Los_Angeles')),
        'absolute' => FALSE,
        'expected' => $positive_19_hours,
      ],
      // In 1970 Sydney did not observe daylight savings time
      // So there is only an 18 hour time interval.
      [
        'input2' => DateTimePlus::createFromFormat('Y-m-d H:i:s', '1970-01-01 00:00:00', new \DateTimeZone('Australia/Sydney')),
        'input1' => DateTimePlus::createFromFormat('Y-m-d H:i:s', '1970-01-01 00:00:00', new \DateTimeZone('America/Los_Angeles')),
        'absolute' => FALSE,
        'expected' => $positive_18_hours,
      ],
      [
        'input1' => DateTimePlus::createFromFormat('U', '3600', new \DateTimeZone('America/Los_Angeles')),
        'input2' => DateTimePlus::createFromTimestamp(0, new \DateTimeZone('UTC')),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DateTimePlus::createFromTimestamp(3600),
        'input2' => DateTimePlus::createFromTimestamp(0),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DateTimePlus::createFromTimestamp(3600),
        'input2' => \DateTime::createFromFormat('U', '0'),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DateTimePlus::createFromTimestamp(3600),
        'input2' => DateTimePlus::createFromTimestamp(0),
        'absolute' => TRUE,
        'expected' => $positive_1_hour,
      ],
      [
        'input1' => DateTimePlus::createFromTimestamp(3600),
        'input2' => \DateTime::createFromFormat('U', '0'),
        'absolute' => TRUE,
        'expected' => $positive_1_hour,
      ],
      [
        'input1' => DateTimePlus::createFromTimestamp(0),
        'input2' => DateTimePlus::createFromTimestamp(0),
        'absolute' => FALSE,
        'expected' => $empty_interval,
      ],
    ];
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DateTimePlusTest::testInvalidDateDiff().
   *
   * @see DateTimePlusTest::testInvalidDateDiff()
   */
  public static function providerTestInvalidDateDiff() {
    return [
      [
        'input1' => DateTimePlus::createFromTimestamp(3600),
        'input2' => '1970-01-01 00:00:00',
        'absolute' => FALSE,
      ],
      [
        'input1' => DateTimePlus::createFromTimestamp(3600),
        'input2' => NULL,
        'absolute' => FALSE,
      ],
    ];
  }

  /**
   * Tests invalid values passed to constructor.
   *
   * @param string $time
   *   A date/time string.
   * @param string[] $errors
   *   An array of error messages.
   *
   * @covers ::__construct
   *
   * @dataProvider providerTestInvalidConstructor
   */
  public function testInvalidConstructor($time, array $errors): void {
    $date = new DateTimePlus($time);

    $this->assertEquals(TRUE, $date->hasErrors());
    $this->assertEquals($errors, $date->getErrors());
  }

  /**
   * Provider for testInvalidConstructor().
   *
   * @return array
   *   An array of invalid date/time strings, and corresponding error messages.
   */
  public static function providerTestInvalidConstructor() {
    return [
      [
        'YYYY-MM-DD',
        [
          'The timezone could not be found in the database',
          'Unexpected character',
          'Double timezone specification',
        ],
      ],
      [
        '2017-MM-DD',
        [
          'Unexpected character',
          'The timezone could not be found in the database',
        ],
      ],
      [
        'YYYY-03-DD',
        [
          'The timezone could not be found in the database',
          'Unexpected character',
          'Double timezone specification',
        ],
      ],
      [
        'YYYY-MM-07',
        [
          'The timezone could not be found in the database',
          'Unexpected character',
          'Double timezone specification',
        ],
      ],
      [
        '2017-13-55',
        [
          'Unexpected character',
        ],
      ],
      [
        'YYYY-MM-DD hh:mm:ss',
        [
          'The timezone could not be found in the database',
          'Unexpected character',
          'Double timezone specification',
        ],
      ],
      [
        '2017-03-07 25:70:80',
        [
          'Unexpected character',
          'Double time specification',
        ],
      ],
      [
        'invalid time string',
        [
          'The timezone could not be found in the database',
          'Double timezone specification',
        ],
      ],
    ];
  }

  /**
   * Tests the $settings['validate_format'] parameter in ::createFromFormat().
   */
  public function testValidateFormat(): void {
    // Check that an input that does not strictly follow the input format will
    // produce the desired date. In this case the year string '11' doesn't
    // precisely match the 'Y' formatter parameter, but PHP will parse it
    // regardless. However, when formatted with the same string, the year will
    // be output with four digits. With the ['validate_format' => FALSE]
    // $settings, this will not thrown an exception.
    $date = DateTimePlus::createFromFormat('Y-m-d H:i:s', '11-03-31 17:44:00', 'UTC', ['validate_format' => FALSE]);
    $this->assertEquals('0011-03-31 17:44:00', $date->format('Y-m-d H:i:s'));

    // Parse the same date with ['validate_format' => TRUE] and make sure we
    // get the expected exception.
    $this->expectException(\UnexpectedValueException::class);
    $date = DateTimePlus::createFromFormat('Y-m-d H:i:s', '11-03-31 17:44:00', 'UTC', ['validate_format' => TRUE]);
  }

  /**
   * Tests setting the default time for date-only objects.
   */
  public function testDefaultDateTime(): void {
    $utc = new \DateTimeZone('UTC');

    $date = DateTimePlus::createFromFormat('Y-m-d H:i:s', '2017-05-23 22:58:00', $utc);
    $this->assertEquals('22:58:00', $date->format('H:i:s'));
    $date->setDefaultDateTime();
    $this->assertEquals('12:00:00', $date->format('H:i:s'));
  }

  /**
   * Tests that object methods are chainable.
   *
   * @covers ::__call
   */
  public function testChainable(): void {
    $date = new DateTimePlus('now', 'Australia/Sydney');

    $date->setTimestamp(12345678);
    $rendered = $date->render();
    $this->assertEquals('1970-05-24 07:21:18 Australia/Sydney', $rendered);

    $date->setTimestamp(23456789);
    $rendered = $date->setTimezone(new \DateTimeZone('America/New_York'))->render();
    $this->assertEquals('1970-09-29 07:46:29 America/New_York', $rendered);

    $date = DateTimePlus::createFromFormat('Y-m-d H:i:s', '1970-05-24 07:21:18', new \DateTimeZone('Australia/Sydney'))
      ->setTimezone(new \DateTimeZone('America/New_York'));
    $rendered = $date->render();
    $this->assertInstanceOf(DateTimePlus::class, $date);
    $this->assertEquals(12345678, $date->getTimestamp());
    $this->assertEquals('1970-05-23 17:21:18 America/New_York', $rendered);
  }

  /**
   * Tests that non-chainable methods work.
   *
   * @covers ::__call
   */
  public function testChainableNonChainable(): void {
    $datetime1 = new DateTimePlus('2009-10-11 12:00:00');
    $datetime2 = new DateTimePlus('2009-10-13 12:00:00');
    $interval = $datetime1->diff($datetime2);
    $this->assertInstanceOf(\DateInterval::class, $interval);
    $this->assertEquals('+2 days', $interval->format('%R%a days'));
  }

  /**
   * Tests that chained calls to non-existent functions throw an exception.
   *
   * @covers ::__call
   */
  public function testChainableNonCallable(): void {
    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('Call to undefined method Drupal\Component\Datetime\DateTimePlus::nonexistent()');
    $date = new DateTimePlus('now', 'Australia/Sydney');
    $date->setTimezone(new \DateTimeZone('America/New_York'))->nonexistent();
  }

  /**
   * @covers ::getPhpDateTime
   */
  public function testGetPhpDateTime(): void {
    $new_york = new \DateTimeZone('America/New_York');
    $berlin = new \DateTimeZone('Europe/Berlin');

    // Test retrieving a cloned copy of the wrapped \DateTime object, and that
    // altering it does not change the DateTimePlus object.
    $datetimeplus = DateTimePlus::createFromFormat('Y-m-d H:i:s', '2017-07-13 22:40:00', $new_york, ['langcode' => 'en']);
    $this->assertEquals(1500000000, $datetimeplus->getTimestamp());
    $this->assertEquals('America/New_York', $datetimeplus->getTimezone()->getName());

    $datetime = $datetimeplus->getPhpDateTime();
    $this->assertInstanceOf('DateTime', $datetime);
    $this->assertEquals(1500000000, $datetime->getTimestamp());
    $this->assertEquals('America/New_York', $datetime->getTimezone()->getName());

    $datetime->setTimestamp(1400000000)->setTimezone($berlin);
    $this->assertEquals(1400000000, $datetime->getTimestamp());
    $this->assertEquals('Europe/Berlin', $datetime->getTimezone()->getName());
    $this->assertEquals(1500000000, $datetimeplus->getTimestamp());
    $this->assertEquals('America/New_York', $datetimeplus->getTimezone()->getName());
  }

}
