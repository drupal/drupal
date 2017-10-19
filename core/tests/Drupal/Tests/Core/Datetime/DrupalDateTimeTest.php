<?php

namespace Drupal\Tests\Core\Datetime;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Datetime\DrupalDateTime
 * @group Datetime
 */
class DrupalDateTimeTest extends UnitTestCase {

  /**
   * Test date diffs.
   *
   * @param mixed $input1
   *   A DrupalDateTime object.
   * @param mixed $input2
   *   Date argument for DrupalDateTime::diff method.
   * @param bool $absolute
   *   Absolute flag for DrupalDateTime::diff method.
   * @param \DateInterval $expected
   *   The expected result of the DrupalDateTime::diff operation.
   *
   * @dataProvider providerTestDateDiff
   */
  public function testDateDiff($input1, $input2, $absolute, \DateInterval $expected) {
    $interval = $input1->diff($input2, $absolute);
    $this->assertEquals($interval, $expected);
  }

  /**
   * Test date diff exception caused by invalid input.
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
  public function testInvalidDateDiff($input1, $input2, $absolute) {
    $this->setExpectedException(\BadMethodCallException::class, 'Method Drupal\Component\Datetime\DateTimePlus::diff expects parameter 1 to be a \DateTime or \Drupal\Component\Datetime\DateTimePlus object');
    $interval = $input1->diff($input2, $absolute);
  }

  /**
   * Provides data for date tests.
   *
   * @return array
   *   An array of arrays, each containing the input parameters for
   *   DrupalDateTimeTest::testDateDiff().
   *
   * @see DrupalDateTimeTest::testDateDiff()
   */
  public function providerTestDateDiff() {

    $settings = ['langcode' => 'en'];

    $utc_tz = new \DateTimeZone('UTC');

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
        'input2' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00', new \DateTimeZone('Australia/Sydney'), $settings),
        'input1' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00', new \DateTimeZone('America/Los_Angeles'), $settings),
        'absolute' => FALSE,
        'expected' => $positive_19_hours,
      ],
      // In 1970 Sydney did not observe daylight savings time
      // So there is only a 18 hour time interval.
      [
        'input2' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '1970-01-01 00:00:00', new \DateTimeZone('Australia/Sydney'), $settings),
        'input1' => DrupalDateTime::createFromFormat('Y-m-d H:i:s', '1970-01-01 00:00:00', new \DateTimeZone('America/Los_Angeles'), $settings),
        'absolute' => FALSE,
        'expected' => $positive_18_hours,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, new \DateTimeZone('America/Los_Angeles'), $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => \DateTime::createFromFormat('U', 0),
        'absolute' => FALSE,
        'expected' => $negative_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'absolute' => TRUE,
        'expected' => $positive_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => \DateTime::createFromFormat('U', 0),
        'absolute' => TRUE,
        'expected' => $positive_1_hour,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
        'input2' => DrupalDateTime::createFromFormat('U', 0, $utc_tz, $settings),
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
  public function providerTestInvalidDateDiff() {
    $settings = ['langcode' => 'en'];
    $utc_tz = new \DateTimeZone('UTC');
    return [
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => '1970-01-01 00:00:00',
        'absolute' => FALSE,
      ],
      [
        'input1' => DrupalDateTime::createFromFormat('U', 3600, $utc_tz, $settings),
        'input2' => NULL,
        'absolute' => FALSE,
      ],
    ];
  }

  /**
   * Tests that object methods are chainable.
   *
   * @covers ::__call
   */
  public function testChainable() {
    $tz = new \DateTimeZone(date_default_timezone_get());
    $date = new DrupalDateTime('now', $tz, ['langcode' => 'en']);

    $date->setTimestamp(12345678);
    $rendered = $date->render();
    $this->assertEquals('1970-05-24 07:21:18 Australia/Sydney', $rendered);

    $date->setTimestamp(23456789);
    $rendered = $date->setTimezone(new \DateTimeZone('America/New_York'))->render();
    $this->assertEquals('1970-09-29 07:46:29 America/New_York', $rendered);
  }

  /**
   * Tests that non-chainable methods work.
   *
   * @covers ::__call
   */
  public function testChainableNonChainable() {
    $tz = new \DateTimeZone(date_default_timezone_get());
    $datetime1 = new DrupalDateTime('2009-10-11 12:00:00', $tz, ['langcode' => 'en']);
    $datetime2 = new DrupalDateTime('2009-10-13 12:00:00', $tz, ['langcode' => 'en']);
    $interval = $datetime1->diff($datetime2);
    $this->assertInstanceOf(\DateInterval::class, $interval);
    $this->assertEquals('+2 days', $interval->format('%R%a days'));
  }

  /**
   * Tests that chained calls to non-existent functions throw an exception.
   *
   * @covers ::__call
   */
  public function testChainableNonCallable() {
    $this->setExpectedException(\BadMethodCallException::class, 'Call to undefined method Drupal\Core\Datetime\DrupalDateTime::nonexistent()');
    $tz = new \DateTimeZone(date_default_timezone_get());
    $date = new DrupalDateTime('now', $tz, ['langcode' => 'en']);
    $date->setTimezone(new \DateTimeZone('America/New_York'))->nonexistent();
  }

}
