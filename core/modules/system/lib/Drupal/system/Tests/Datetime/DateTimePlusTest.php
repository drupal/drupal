<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Datetime\DateTimePlusTest.
 */

namespace Drupal\system\Tests\Datetime;

use Drupal\simpletest\UnitTestBase;
use Drupal\Component\Datetime\DateTimePlus;
use DateTimeZone;

class DateTimePlusTest extends UnitTestBase {

  /**
   * Test information.
   */
  public static function getInfo() {
    return array(
      'name' => 'DateTimePlus',
      'description' => 'Test DateTimePlus functionality.',
      'group' => 'Datetime',
    );
  }

  /**
   * Set up required modules.
   */
  public static $modules = array();

  /**
   * Test creating dates from string input.
   */
  public function testDateStrings() {

    // Create date object from datetime string.
    $input = '2009-03-07 10:30';
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2009-03-07T10:30:00-06:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone): should be $expected, found $value.");

    // Same during daylight savings time.
    $input = '2009-06-07 10:30';
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2009-06-07T10:30:00-05:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone): should be $expected, found $value.");

    // Create date object from date string.
    $input = '2009-03-07';
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2009-03-07T00:00:00-06:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone): should be $expected, found $value.");

    // Same during daylight savings time.
    $input = '2009-06-07';
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2009-06-07T00:00:00-05:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone): should be $expected, found $value.");

    // Create date object from date string.
    $input = '2009-03-07 10:30';
    $timezone = 'Australia/Canberra';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2009-03-07T10:30:00+11:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone): should be $expected, found $value.");

    // Same during daylight savings time.
    $input = '2009-06-07 10:30';
    $timezone = 'Australia/Canberra';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2009-06-07T10:30:00+10:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone): should be $expected, found $value.");

  }

  /**
   * Test creating dates from arrays of date parts.
   */
  function testDateArrays() {

    // Create date object from date array, date only.
    $input = array('year' => 2010, 'month' => 2, 'day' => 28);
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2010-02-28T00:00:00-06:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus(array('year' => 2010, 'month' => 2, 'day' => 28), $timezone): should be $expected, found $value.");

    // Create date object from date array with hour.
    $input = array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10);
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2010-02-28T10:00:00-06:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus(array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10), $timezone): should be $expected, found $value.");

    // Create date object from date array, date only.
    $input = array('year' => 2010, 'month' => 2, 'day' => 28);
    $timezone = 'Europe/Berlin';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2010-02-28T00:00:00+01:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus(array('year' => 2010, 'month' => 2, 'day' => 28), $timezone): should be $expected, found $value.");

    // Create date object from date array with hour.
    $input = array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10);
    $timezone = 'Europe/Berlin';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '2010-02-28T10:00:00+01:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus(array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 10), $timezone): should be $expected, found $value.");

  }

  /**
   * Test creating dates from timestamps.
   */
  function testDateTimestamp() {

    // Create date object from a unix timestamp and display it in
    // local time.
    $input = 0;
    $timezone = 'UTC';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '1970-01-01T00:00:00+00:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone): should be $expected, found $value.");

    $expected = 'UTC';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone is $value: should be $expected.");
    $expected = 0;
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset is $value: should be $expected.");

    $timezone = 'America/Los_Angeles';
    $date->setTimezone(new DateTimeZone($timezone));
    $value = $date->format('c');
    $expected = '1969-12-31T16:00:00-08:00';
    $this->assertEqual($expected, $value, "Test \$date->setTimezone(new DateTimeZone($timezone)): should be $expected, found $value.");

    $expected = 'America/Los_Angeles';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone should be $expected, found $value.");
    $expected = '-28800';
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset should be $expected, found $value.");

    // Create a date using the timestamp of zero, then display its
    // value both in UTC and the local timezone.
    $input = 0;
    $timezone = 'America/Los_Angeles';
    $date = new DateTimePlus($input, $timezone);
    $offset = $date->getOffset();
    $value = $date->format('c');
    $expected = '1969-12-31T16:00:00-08:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone):  should be $expected, found $value.");

    $expected = 'America/Los_Angeles';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone should be $expected, found $value.");
    $expected = '-28800';
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset should be $expected, found $value.");

    $timezone = 'UTC';
    $date->setTimezone(new DateTimeZone($timezone));
    $value = $date->format('c');
    $expected = '1970-01-01T00:00:00+00:00';
    $this->assertEqual($expected, $value, "Test \$date->setTimezone(new DateTimeZone($timezone)): should be $expected, found $value.");

    $expected = 'UTC';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone should be $expected, found $value.");
    $expected = '0';
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset should be $expected, found $value.");
  }

  /**
   * Test timezone manipulation.
   */
  function testTimezoneConversion() {

    // Create date object from datetime string in UTC, and convert
    // it to a local date.
    $input = '1970-01-01 00:00:00';
    $timezone = 'UTC';
    $date = new DateTimePlus($input, $timezone);
    $value = $date->format('c');
    $expected = '1970-01-01T00:00:00+00:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus('$input', '$timezone'): should be $expected, found $value.");

    $expected = 'UTC';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone is $value: should be $expected.");
    $expected = 0;
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset is $value: should be $expected.");

    $timezone = 'America/Los_Angeles';
    $date->setTimezone(new DateTimeZone($timezone));
    $value = $date->format('c');
    $expected = '1969-12-31T16:00:00-08:00';
    $this->assertEqual($expected, $value, "Test \$date->setTimezone(new DateTimeZone($timezone)): should be $expected, found $value.");

    $expected = 'America/Los_Angeles';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone should be $expected, found $value.");
    $expected = '-28800';
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset should be $expected, found $value.");

    // Convert the local time to UTC using string input.
    $input = '1969-12-31 16:00:00';
    $timezone = 'America/Los_Angeles';
    $date = new DateTimePlus($input, $timezone);
    $offset = $date->getOffset();
    $value = $date->format('c');
    $expected = '1969-12-31T16:00:00-08:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus('$input', '$timezone'):  should be $expected, found $value.");

    $expected = 'America/Los_Angeles';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone should be $expected, found $value.");
    $expected = '-28800';
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset should be $expected, found $value.");

    $timezone = 'UTC';
    $date->setTimezone(new DateTimeZone($timezone));
    $value = $date->format('c');
    $expected = '1970-01-01T00:00:00+00:00';
    $this->assertEqual($expected, $value, "Test \$date->setTimezone(new DateTimeZone($timezone)): should be $expected, found $value.");

    $expected = 'UTC';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone should be $expected, found $value.");
    $expected = '0';
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset should be $expected, found $value.");

    // Convert the local time to UTC using string input.
    $input = '1969-12-31 16:00:00';
    $timezone = 'Europe/Warsaw';
    $date = new DateTimePlus($input, $timezone);
    $offset = $date->getOffset();
    $value = $date->format('c');
    $expected = '1969-12-31T16:00:00+01:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus('$input', '$timezone'):  should be $expected, found $value.");

    $expected = 'Europe/Warsaw';
    $value = $date->getTimeZone()->getName();
    $this->assertEqual($expected, $value, "The current timezone should be $expected, found $value.");
    $expected = '+3600';
    $value = $date->getOffset();
    $this->assertEqual($expected, $value, "The current offset should be $expected, found $value.");

  }

  /**
   * Test creating dates from format strings.
   */
  function testDateFormat() {

     // Create a year-only date.
    $input = '2009';
    $timezone = NULL;
    $format = 'Y';
    $date = new DateTimePlus($input, $timezone, $format);
    $value = $date->format('Y');
    $expected = '2009';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone, $format): should be $expected, found $value.");

     // Create a month and year-only date.
    $input = '2009-10';
    $timezone = NULL;
    $format = 'Y-m';
    $date = new DateTimePlus($input, $timezone, $format);
    $value = $date->format('Y-m');
    $expected = '2009-10';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone, $format): should be $expected, found $value.");

     // Create a time-only date.
    $input = 'T10:30:00';
    $timezone = NULL;
    $format = '\TH:i:s';
    $date = new DateTimePlus($input, $timezone, $format);
    $value = $date->format('H:i:s');
    $expected = '10:30:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone, $format): should be $expected, found $value.");

     // Create a time-only date.
    $input = '10:30:00';
    $timezone = NULL;
    $format = 'H:i:s';
    $date = new DateTimePlus($input, $timezone, $format);
    $value = $date->format('H:i:s');
    $expected = '10:30:00';
    $this->assertEqual($expected, $value, "Test new DateTimePlus($input, $timezone, $format): should be $expected, found $value.");

  }

  /**
   * Test invalid date handling.
   */
  function testInvalidDates() {

    // Test for invalid month names when we are using a short version
    // of the month.
    $input = '23 abc 2012';
    $timezone = NULL;
    $format = 'd M Y';
    $date = new DateTimePlus($input, $timezone, $format);
    $this->assertNotEqual(count($date->getErrors()), 0, "$input contains an invalid month name and produces errors.");

     // Test for invalid hour.
    $input = '0000-00-00T45:30:00';
    $timezone = NULL;
    $format = 'Y-m-d\TH:i:s';
    $date = new DateTimePlus($input, $timezone, $format);
    $this->assertNotEqual(count($date->getErrors()), 0, "$input contains an invalid hour and produces errors.");

     // Test for invalid day.
    $input = '0000-00-99T05:30:00';
    $timezone = NULL;
    $format = 'Y-m-d\TH:i:s';
    $date = new DateTimePlus($input, $timezone, $format);
    $this->assertNotEqual(count($date->getErrors()), 0, "$input contains an invalid day and produces errors.");

     // Test for invalid month.
    $input = '0000-75-00T15:30:00';
    $timezone = NULL;
    $format = 'Y-m-d\TH:i:s';
    $date = new DateTimePlus($input, $timezone, $format);
    $this->assertNotEqual(count($date->getErrors()), 0, "$input contains an invalid month and produces errors.");

     // Test for invalid year.
    $input = '11-08-01T15:30:00';
    $timezone = NULL;
    $format = 'Y-m-d\TH:i:s';
    $date = new DateTimePlus($input, $timezone, $format);
    $this->assertNotEqual(count($date->getErrors()), 0, "$input contains an invalid year and produces errors.");

    // Test for invalid year from date array. 10000 as a year will
    // create an exception error in the PHP DateTime object.
    $input = array('year' => 10000, 'month' => 7, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0);
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $this->assertNotEqual(count($date->getErrors()), 0, "array('year' => 10000, 'month' => 7, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0) contains an invalid year and produces errors.");

    // Test for invalid month from date array.
    $input = array('year' => 2010, 'month' => 27, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0);
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $this->assertNotEqual(count($date->getErrors()), 0, "array('year' => 2010, 'month' => 27, 'day' => 8, 'hour' => 8, 'minute' => 0, 'second' => 0) contains an invalid month and produces errors.");

    // Test for invalid hour from date array.
    $input = array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 80, 'minute' => 0, 'second' => 0);
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $this->assertNotEqual(count($date->getErrors()), 0, "array('year' => 2010, 'month' => 2, 'day' => 28, 'hour' => 80, 'minute' => 0, 'second' => 0) contains an invalid hour and produces errors.");

    // Test for invalid minute from date array.
    $input = array('year' => 2010, 'month' => 7, 'day' => 8, 'hour' => 8, 'minute' => 88, 'second' => 0);
    $timezone = 'America/Chicago';
    $date = new DateTimePlus($input, $timezone);
    $this->assertNotEqual(count($date->getErrors()), 0, "array('year' => 2010, 'month' => 7, 'day' => 8, 'hour' => 8, 'minute' => 88, 'second' => 0) contains an invalid minute and produces errors.");

  }

  /**
   * Test that DrupalDateTime can detect the right timezone to use.
   * When specified or not.
   */
  public function testDateTimezone() {
    global $user;

    $date_string = '2007-01-31 21:00:00';

    // Detect the system timezone.
    $system_timezone = date_default_timezone_get();

    // Create a date object with an unspecified timezone, which should
    // end up using the system timezone.
    $date = new DateTimePlus($date_string);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == $system_timezone, 'DateTimePlus uses the system timezone when there is no site timezone.');

    // Create a date object with a specified timezone name.
    $date = new DateTimePlus($date_string, 'America/Yellowknife');
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'America/Yellowknife', 'DateTimePlus uses the specified timezone if provided.');

    // Create a date object with a timezone object.
    $date = new DateTimePlus($date_string, new \DateTimeZone('Australia/Canberra'));
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'Australia/Canberra', 'DateTimePlus uses the specified timezone if provided.');

    // Create a date object with another date object.
    $new_date = new DateTimePlus('now', 'Pacific/Midway');
    $date = new DateTimePlus($new_date);
    $timezone = $date->getTimezone()->getName();
    $this->assertTrue($timezone == 'Pacific/Midway', 'DateTimePlus uses the specified timezone if provided.');

  }
}
