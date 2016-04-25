<?php

namespace Drupal\Tests\Component\Datetime;

use Drupal\Tests\UnitTestCase;
use Drupal\Component\Datetime\DateTimePlus;

/**
 * @coversDefaultClass \Drupal\Component\Datetime\DateTimePlus
 * @group Datetime
 */
class DateTimePlusTest extends UnitTestCase {

  /**
   * Test creating dates from string and array input.
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
  public function testDates($input, $timezone, $expected) {
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');

    if (is_array($input)) {
      $input = var_export($input, TRUE);
    }
    $this->assertEquals($expected, $value, sprintf("Test new DateTimePlus(%s, %s): should be %s, found %s.", $input, $timezone, $expected, $value));
  }

  /**
   * Test creating dates from string and array input.
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
  public function testDateArrays($input, $timezone, $expected) {
    $date = DateTimePlus::createFromArray($input, $timezone);
    $value = $date->format('c');

    if (is_array($input)) {
      $input = var_export($input, TRUE);
    }
    $this->assertEquals($expected, $value, sprintf("Test new DateTimePlus(%s, %s): should be %s, found %s.", $input, $timezone, $expected, $value));
  }

  /**
   * Test creating dates from invalid array input.
   *
   * @param mixed $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   *
   * @dataProvider providerTestInvalidDateArrays
   * @expectedException \Exception
   */
  public function testInvalidDateArrays($input, $timezone) {
    $this->assertInstanceOf(
      '\Drupal\Component\DateTimePlus',
      DateTimePlus::createFromArray($input, $timezone)
    );
  }

  /**
   * Test creating dates from timestamps, and manipulating timezones.
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
  public function testTimestamp($input, array $initial, array $transform) {
    // Initialize a new date object.
    $date = DateTimePlus::createFromTimestamp($input, $initial['timezone']);
    $this->assertDateTimestamp($date, $input, $initial, $transform);
  }

  /**
   * Test creating dates from datetime strings.
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
  public function testDateTimestamp($input, array $initial, array $transform) {
    // Initialize a new date object.
    $date = new DateTimePlus($input, $initial['timezone']);
    $this->assertDateTimestamp($date, $input, $initial, $transform);
  }

  /**
   * Assertion helper for testTimestamp and testDateTimestamp since they need
   * different dataProviders.
   *
   * @param DateTimePlus $date
   *   DateTimePlus to test.
   * @input mixed $input
   *   The original input passed to the test method.
   * @param array $initial
   *   @see testTimestamp()
   * @param array $transform
   *   @see testTimestamp()
   */
  public function assertDateTimestamp($date, $input, $initial, $transform) {
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
   * Test creating dates from format strings.
   *
   * @param string $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $format_date
   *   Format argument for DateTimePlus::format().
   * @param string $expected
   *   Expected output from DateTimePlus::format().
   *
   * @dataProvider providerTestDateFormat
   */
  public function testDateFormat($input, $timezone, $format, $format_date, $expected) {
    $date = DateTimePlus::createFromFormat($format, $input, $timezone);
    $value = $date->format($format_date);
    $this->assertEquals($expected, $value, sprintf("Test new DateTimePlus(%s, %s, %s): should be %s, found %s.", $input, $timezone, $format, $expected, $value));
  }

  /**
   * Test invalid date handling.
   *
   * @param mixed $input
   *   Input argument for DateTimePlus.
   * @param string $timezone
   *   Timezone argument for DateTimePlus.
   * @param string $format
   *   Format argument for DateTimePlus.
   * @param string $message
   *   Message to print if no errors are thrown by the invalid dates.
   *
   * @dataProvider providerTestInvalidDates
   * @expectedException \Exception
   */
  public function testInvalidDates($input, $timezone, $format, $message) {
    DateTimePlus::createFromFormat($format, $input, $timezone);
  }

  /**
   * Tests that DrupalDateTime can detect the right timezone to use.
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
  public function testDateTimezone($input, $timezone, $expected_timezone, $message) {
    $date = new DateTimePlus($input, $timezone);
    $timezone = $date->getTimezone()->getName();
    $this->assertEquals($timezone, $expected_timezone, $message);
  }

  /**
   * Test that DrupalDateTime can detect the right timezone to use when
   * constructed from a datetime object.
   */
  public function testDateTimezoneWithDateTimeObject() {
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
   * @see DateTimePlusTest::testDates().
   */
  public function providerTestDates() {
    return array(
      // String input.
      // Create date object from datetime string.
      array('2009-03-07 10:30', 'America/Chicago', '2009-03-07T10:30:00-06:00'),
      // Same during daylight savings time.
      array('2009-06-07 10:30', 'America/Chicago', '2009-06-07T10:30:00-05:00'),
      // Create date object from date string.
      array('2009-03-07', 'America/Chicago', '2009-03-07T00:00:00-06:00'),
      // Same during daylight savings time.
      array('2009-06-07', 'America/Chicago', '2009-06-07T00:00:00-05:00'),
      // Create date object from date string.
      array('2009-03-07 10:30', 'Australia/Canberra', '2009-03-07T10:30:00+11:00'),
      // Same during daylight savings time.
      array('2009-06-07 10:30', 'Australia/Canberra', '2009-06-07T10:30:00+10:00'),
    );
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DateTimePlusTest::testDates().
   *
   * @see DateTimePlusTest::testDates().
   */
  public function providerTestDateArrays() {
    return array(
      // Array input.
      // Create date object from date array, date only.
      array(array('year' => 2010, 'month' => 2, 'day' => 28), 'America/Chicago', '2010-02-28T00:00:00-06:00'),
      // Create date object from date array with hour.
      array(array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10), 'America/Chicago', '2010-02-28T10:00:00-06:00'),
      // Create date object from date array, date only.
      array(array('year' => 2010, 'month' => 2, 'day' => 28), 'Europe/Berlin', '2010-02-28T00:00:00+01:00'),
      // Create date object from date array with hour.
      array(array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10), 'Europe/Berlin', '2010-02-28T10:00:00+01:00'),
    );
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
  public function providerTestDateFormat() {
    return array(
      // Create a year-only date.
      array('2009', NULL, 'Y', 'Y', '2009'),
      // Create a month and year-only date.
      array('2009-10', NULL, 'Y-m', 'Y-m', '2009-10'),
      // Create a time-only date.
      array('T10:30:00', NULL, '\TH:i:s', 'H:i:s', '10:30:00'),
      // Create a time-only date.
      array('10:30:00', NULL, 'H:i:s', 'H:i:s', '10:30:00'),
    );
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
  public function providerTestInvalidDates() {
    return array(
      // Test for invalid month names when we are using a short version
      // of the month.
      array('23 abc 2012', NULL, 'd M Y', "23 abc 2012 contains an invalid month name and did not produce errors."),
      // Test for invalid hour.
      array('0000-00-00T45:30:00', NULL, 'Y-m-d\TH:i:s', "0000-00-00T45:30:00 contains an invalid hour and did not produce errors."),
      // Test for invalid day.
      array('0000-00-99T05:30:00', NULL, 'Y-m-d\TH:i:s', "0000-00-99T05:30:00 contains an invalid day and did not produce errors."),
      // Test for invalid month.
      array('0000-75-00T15:30:00', NULL, 'Y-m-d\TH:i:s', "0000-75-00T15:30:00 contains an invalid month and did not produce errors."),
      // Test for invalid year.
      array('11-08-01T15:30:00', NULL, 'Y-m-d\TH:i:s', "11-08-01T15:30:00 contains an invalid year and did not produce errors."),

    );
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
  public function providerTestInvalidDateArrays() {
    return array(
      // One year larger than the documented upper limit of checkdate().
      array(array('year' => 32768, 'month' => 1, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0), 'America/Chicago'),
      // One year smaller than the documented lower limit of checkdate().
      array(array('year' => 0, 'month' => 1, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0), 'America/Chicago'),
      // Test for invalid month from date array.
      array(array('year' => 2010, 'month' => 27, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0), 'America/Chicago'),
      // Test for invalid hour from date array.
      array(array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 80, 'minute' => 0, 'second' => 0), 'America/Chicago'),
      // Test for invalid minute from date array.
      array(array('year' => 2010, 'month' => 7, 'day' => 8, 'hour' => 8, 'minute' => 88, 'second' => 0), 'America/Chicago'),
      // Regression test for https://www.drupal.org/node/2084455.
      array(array('hour' => 59, 'minute' => 1, 'second' => 1), 'America/Chicago'),
    );
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
  public function providerTestDateTimezone() {
    // Use a common date for most of the tests.
    $date_string = '2007-01-31 21:00:00';

    // Detect the system timezone.
    $system_timezone = date_default_timezone_get();

    return array(
      // Create a date object with an unspecified timezone, which should
      // end up using the system timezone.
      array($date_string, NULL, $system_timezone, 'DateTimePlus uses the system timezone when there is no site timezone.'),
      // Create a date object with a specified timezone name.
      array($date_string, 'America/Yellowknife', 'America/Yellowknife', 'DateTimePlus uses the specified timezone if provided.'),
      // Create a date object with a timezone object.
      array($date_string, new \DateTimeZone('Australia/Canberra'), 'Australia/Canberra', 'DateTimePlus uses the specified timezone if provided.'),
      // Create a date object with another date object.
      array(new DateTimePlus('now', 'Pacific/Midway'), NULL, 'Pacific/Midway', 'DateTimePlus uses the specified timezone if provided.'),
    );
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
  public function providerTestTimestamp() {
    return array(
      // Create date object from a unix timestamp and display it in
      // local time.
      array(
        'input' => 0,
        'initial' => array(
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ),
        'transform' => array(
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ),
      ),
      // Create a date using the timestamp of zero, then display its
      // value both in UTC and the local timezone.
      array(
        'input' => 0,
        'initial' => array(
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ),
        'transform' => array(
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ),
      ),
    );
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
  public function providerTestDateTimestamp() {
    return array(
      // Create date object from datetime string in UTC, and convert
      // it to a local date.
      array(
        'input' => '1970-01-01 00:00:00',
        'initial' => array(
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ),
        'transform' => array(
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ),
      ),
      // Convert the local time to UTC using string input.
      array(
        'input' => '1969-12-31 16:00:00',
        'initial' => array(
          'timezone' => 'America/Los_Angeles',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00-08:00',
          'expected_timezone' => 'America/Los_Angeles',
          'expected_offset' => '-28800',
        ),
        'transform' => array(
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1970-01-01T00:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ),
      ),
      // Convert the local time to UTC using string input.
      array(
        'input' => '1969-12-31 16:00:00',
        'initial' => array(
          'timezone' => 'Europe/Warsaw',
          'format' => 'c',
          'expected_date' => '1969-12-31T16:00:00+01:00',
          'expected_timezone' => 'Europe/Warsaw',
          'expected_offset' => '+3600',
        ),
        'transform' => array(
          'timezone' => 'UTC',
          'format' => 'c',
          'expected_date' => '1969-12-31T15:00:00+00:00',
          'expected_timezone' => 'UTC',
          'expected_offset' => 0,
        ),
      ),
    );
  }

}
